<?php
declare(strict_types=1);

// Web-Installer: Einrichtung komplett im Browser, ohne Shell/SSH und ohne
// Composer (vendor/ wird mitgeliefert).
//
// Ablauf: Voraussetzungen prüfen → Datenbank anlegen → ersten Administrator
// anlegen. Sobald ein Administrator existiert, führt diese Seite keine
// Aktionen mehr aus. Spätere Datenbank-Updates laufen automatisch beim
// normalen Seitenaufruf (siehe src/Migration.php).

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/config.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

ini_set('display_errors', '0');
session_name('kv_install_sid');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

function e(?string $wert): string
{
    return htmlspecialchars($wert ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['install_csrf'];
}

function csrf_ok(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($_SESSION['install_csrf'] ?? '', $token);
}

/** @return string[] gefundene Probleme; leer = alles in Ordnung */
function pruefe_voraussetzungen(): array
{
    $probleme = [];
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $probleme[] = 'PHP 8.1 oder neuer wird benötigt (installiert: ' . PHP_VERSION . '). Die PHP-Version lässt sich meist in der Hoster-Verwaltung umstellen.';
    }
    foreach (['pdo_sqlite', 'zip', 'gd', 'mbstring'] as $ext) {
        if (!extension_loaded($ext)) {
            $probleme[] = "Die PHP-Erweiterung „{$ext}“ fehlt auf diesem Server.";
        }
    }
    if (!is_file(APP_ROOT . '/vendor/autoload.php')) {
        $probleme[] = 'Der Ordner vendor/ fehlt. Bitte das komplette Projekt inklusive vendor/ hochladen.';
    }
    foreach (['data', 'data/import_tmp', 'data/cache', 'data/logs', 'bilder', 'backups'] as $ordner) {
        $pfad = APP_ROOT . '/' . $ordner;
        if (!is_dir($pfad)) {
            @mkdir($pfad, 0755, true);
        }
        if (!is_dir($pfad) || !is_writable($pfad)) {
            $probleme[] = "Der Ordner „{$ordner}/“ fehlt oder ist für den Webserver nicht beschreibbar (Rechte z. B. auf 755 setzen).";
        }
    }
    return $probleme;
}

function admin_vorhanden(): bool
{
    if (!App\Database::existiert()) {
        return false;
    }
    try {
        $pdo = App\Database::oeffne();
        $tabelle = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'benutzer'")->fetchColumn();
        return $tabelle !== false && (int) $pdo->query("SELECT COUNT(*) FROM benutzer WHERE rolle = 'admin'")->fetchColumn() > 0;
    } catch (\Throwable $e) {
        error_log('Installer: ' . $e);
        return false;
    }
}

$probleme = pruefe_voraussetzungen();
if ($probleme === []) {
    require APP_ROOT . '/vendor/autoload.php';
    ini_set('error_log', LOG_PATH . '/php-fehler.log');
}

$gesperrt = $probleme === [] && admin_vorhanden();
$dbBereit = $probleme === [] && App\Database::existiert();
$meldungFehler = null;
$meldungErfolg = null;
$pruefung = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$gesperrt && $probleme === []) {
    if (!csrf_ok()) {
        $meldungFehler = 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden und erneut versuchen.';
    } elseif (($_POST['aktion'] ?? '') === 'migrieren') {
        try {
            App\Migration::aktualisiere(App\Database::oeffne());
            $meldungErfolg = 'Die Datenbank wurde eingerichtet.';
            $dbBereit = true;
        } catch (\Throwable $e) {
            error_log('Installer: ' . $e);
            $meldungFehler = 'Die Datenbank konnte nicht eingerichtet werden. Bitte Schreibrechte des Ordners data/ prüfen.';
        }
    } elseif (($_POST['aktion'] ?? '') === 'admin_anlegen' && $dbBereit) {
        $benutzername = trim((string) ($_POST['benutzername'] ?? ''));
        $echterName = trim((string) ($_POST['echter_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $passwort = (string) ($_POST['passwort'] ?? '');

        $meldungFehler = App\BenutzerRepository::benutzernameFehler($benutzername)
            ?? App\BenutzerRepository::stammdatenFehler($echterName, $email)
            ?? App\Auth::passwortFehler($passwort, (string) ($_POST['passwort_wiederholen'] ?? ''));

        if ($meldungFehler === null) {
            try {
                $pdo = App\Database::oeffne();
                App\Migration::aktualisiere($pdo);
                (new App\BenutzerRepository($pdo))->anlegen($benutzername, $echterName, $email, $passwort, 'admin', []);
                App\Protokoll::schreibe('installation', "Administrator „{$benutzername}“ angelegt", []);
                $meldungErfolg = "Administrator „{$benutzername}“ wurde angelegt. Die Installation ist abgeschlossen.";
                $gesperrt = true;
                // Einmalig direkt nach der Einrichtung: sind geschützte Ordner von außen erreichbar?
                $pruefung = App\Sicherheitscheck::pruefeOrdner();
            } catch (\Throwable $e) {
                error_log('Installer: ' . $e);
                $meldungFehler = 'Der Administrator konnte nicht angelegt werden.';
            }
        }
    }
}

$appName = $probleme === [] ? App\Einstellungen::appName() : APP_NAME;
$oeffentlich = $pruefung !== null ? array_keys($pruefung, App\Sicherheitscheck::OEFFENTLICH, true) : [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Installation · <?= e($appName) ?></title>
<link rel="stylesheet" href="/assets/fonts.css?v=<?= rawurlencode(APP_VERSION) ?>">
<link rel="stylesheet" href="/assets/tokens.css?v=<?= rawurlencode(APP_VERSION) ?>">
<link rel="stylesheet" href="/assets/style.css?v=<?= rawurlencode(APP_VERSION) ?>">
</head>
<body>
<main class="hauptinhalt" style="max-width:640px;">
    <h1 class="gruppenname">Installation – <?= e($appName) ?></h1>

    <?php if ($meldungFehler): ?><div class="flash flash--fehler"><?= e($meldungFehler) ?></div><?php endif; ?>
    <?php if ($meldungErfolg): ?><div class="flash flash--erfolg"><?= e($meldungErfolg) ?></div><?php endif; ?>

    <?php if ($probleme !== []): ?>
        <div class="karte">
            <h3>Voraussetzungen nicht erfüllt</h3>
            <ul><?php foreach ($probleme as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
            <p class="text-klein text-sekundaer">Nach dem Beheben diese Seite neu laden.</p>
        </div>

    <?php elseif ($gesperrt): ?>
        <?php if ($pruefung !== null): ?>
            <div class="karte">
                <h3>Sicherheitsprüfung</h3>
                <?php if ($oeffentlich !== []): ?>
                    <div class="flash flash--fehler">Achtung: Die Ordner <?= e(implode(', ', array_map(static fn($o) => $o . '/', $oeffentlich))) ?> sind von außen abrufbar – der Webserver wertet die mitgelieferten .htaccess-Sperren nicht aus. Bitte beim Hoster aktivieren lassen oder die Ordner in der Hoster-Verwaltung per Verzeichnisschutz sperren, bevor echte Daten importiert werden.</div>
                <?php elseif (in_array(App\Sicherheitscheck::UNBEKANNT, $pruefung, true)): ?>
                    <p class="text-sekundaer">Die Prüfung war auf diesem Server nicht vollständig möglich. Sie kann später unter „System“ wiederholt werden.</p>
                <?php else: ?>
                    <p class="status-ok">Alle geschützten Ordner sind von außen gesperrt.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="karte">
            <h3>Installation abgeschlossen</h3>
            <p>Es ist ein Administrator-Konto eingerichtet; diese Seite führt keine weiteren Aktionen aus. Updates der Datenbank laufen künftig automatisch.</p>
            <p class="text-klein text-sekundaer">Empfohlen: <code>install.php</code> jetzt vom Server löschen (z. B. per FTP oder Datei-Manager des Hosters).</p>
            <a href="/login.php" class="btn btn--primaer">Zum Login</a>
        </div>

    <?php elseif (!$dbBereit): ?>
        <div class="karte">
            <h3>Schritt 1 von 2: Datenbank einrichten</h3>
            <p class="text-sekundaer">Legt die Datenbank mit einem zufälligen, nicht erratbaren Dateinamen im Ordner data/ an.</p>
            <form method="post" action="/install.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aktion" value="migrieren">
                <button type="submit" class="btn btn--primaer">Datenbank jetzt einrichten</button>
            </form>
        </div>

    <?php else: ?>
        <div class="karte">
            <h3>Schritt 2 von 2: Administrator anlegen</h3>
            <p class="text-sekundaer">Dieses Konto erhält vollen Zugriff (Import, Benutzer, Backup).</p>
            <form method="post" action="/install.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aktion" value="admin_anlegen">
                <div class="feld">
                    <label for="benutzername">Benutzername (3–50 Zeichen)</label>
                    <input type="text" id="benutzername" name="benutzername" required minlength="3" maxlength="50" autofocus value="<?= e((string) ($_POST['benutzername'] ?? '')) ?>">
                </div>
                <div class="feldreihe">
                    <div class="feld">
                        <label for="echter_name">Echter Name</label>
                        <input type="text" id="echter_name" name="echter_name" maxlength="100" value="<?= e((string) ($_POST['echter_name'] ?? '')) ?>">
                    </div>
                    <div class="feld">
                        <label for="email">E-Mail</label>
                        <input type="email" id="email" name="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>">
                    </div>
                </div>
                <div class="feldreihe">
                    <div class="feld">
                        <label for="passwort">Passwort (mind. 8 Zeichen)</label>
                        <input type="password" id="passwort" name="passwort" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="feld">
                        <label for="passwort_wiederholen">Passwort wiederholen</label>
                        <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn--primaer">Administrator anlegen</button>
            </form>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
