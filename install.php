<?php
declare(strict_types=1);

// Web-Installer: komplette Einrichtung läuft browserbasiert über diese
// eine Datei – kein Shell-/SSH-Zugriff und kein Composer auf dem Server
// nötig, da der vendor/-Ordner bereits mitgeliefert wird.
//
// Ablauf: Voraussetzungen prüfen → Datenbank anlegen → ersten
// Administrator anlegen. Sobald ein Administrator existiert, verweigert
// diese Seite jede weitere Aktion (Schutz gegen Missbrauch). Danach kann
// install.php gefahrlos auf dem Server bleiben oder gelöscht werden.

require __DIR__ . '/src/config.php';

session_name('kv_install_sid');
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

/** @return string[] Liste gefundener Probleme, leer = alles ok. */
function pruefe_voraussetzungen(): array
{
    $probleme = [];

    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $probleme[] = 'PHP 8.1 oder neuer wird benötigt (aktuell installiert: ' . PHP_VERSION . ').';
    }
    foreach (['pdo_sqlite', 'zip', 'gd', 'mbstring'] as $ext) {
        if (!extension_loaded($ext)) {
            $probleme[] = "Die PHP-Erweiterung „{$ext}“ fehlt auf diesem Server.";
        }
    }
    if (!is_file(APP_ROOT . '/vendor/autoload.php')) {
        $probleme[] = 'Der Ordner vendor/ fehlt. Bitte das komplette Projekt inklusive vendor/-Ordner hochladen.';
    }

    foreach (['data', 'data/import_tmp', 'bilder', 'thumbs', 'backups'] as $ordner) {
        $pfad = APP_ROOT . '/' . $ordner;
        if (!is_dir($pfad)) {
            @mkdir($pfad, 0755, true);
        }
        if (!is_dir($pfad) || !is_writable($pfad)) {
            $probleme[] = "Der Ordner „{$ordner}/“ existiert nicht oder ist für den Webserver nicht beschreibbar (Rechte prüfen, z. B. 755).";
        }
    }

    return $probleme;
}

/** @return array{datenbank_bereit: bool, admin_vorhanden: bool, fehler: ?string} */
function pruefe_installations_stand(): array
{
    if (!is_file(APP_ROOT . '/vendor/autoload.php')) {
        return ['datenbank_bereit' => false, 'admin_vorhanden' => false, 'fehler' => null];
    }
    require_once APP_ROOT . '/vendor/autoload.php';

    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tabelle = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='benutzer'")->fetch();
        if ($tabelle === false) {
            return ['datenbank_bereit' => false, 'admin_vorhanden' => false, 'fehler' => null];
        }
        $anzahlAdmins = (int) $pdo->query("SELECT COUNT(*) AS n FROM benutzer WHERE rolle = 'admin'")->fetch()['n'];
        return ['datenbank_bereit' => true, 'admin_vorhanden' => $anzahlAdmins > 0, 'fehler' => null];
    } catch (\Throwable $e) {
        return ['datenbank_bereit' => false, 'admin_vorhanden' => false, 'fehler' => $e->getMessage()];
    }
}

$lockDatei = APP_ROOT . '/data/installed.lock';
$probleme = pruefe_voraussetzungen();
$stand = $probleme === [] ? pruefe_installations_stand() : ['datenbank_bereit' => false, 'admin_vorhanden' => false, 'fehler' => null];
$gesperrt = is_file($lockDatei) || $stand['admin_vorhanden'];

$meldungFehler = null;
$meldungErfolg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$gesperrt && $probleme === []) {
    if (!csrf_ok()) {
        $meldungFehler = 'Ungültige Anfrage (Sitzung abgelaufen). Bitte Seite neu laden und erneut versuchen.';
    } else {
        $aktion = (string) ($_POST['aktion'] ?? '');

        if ($aktion === 'migrieren') {
            try {
                if (!is_dir(dirname(DB_PATH))) {
                    mkdir(dirname(DB_PATH), 0755, true);
                }
                $pdo = new PDO('sqlite:' . DB_PATH);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec((string) file_get_contents(APP_ROOT . '/migrations/schema.sql'));
                $meldungErfolg = 'Datenbank wurde erfolgreich eingerichtet.';
                $stand = pruefe_installations_stand();
            } catch (\Throwable $e) {
                $meldungFehler = 'Datenbank konnte nicht eingerichtet werden: ' . $e->getMessage();
            }
        } elseif ($aktion === 'admin_anlegen' && $stand['datenbank_bereit']) {
            $benutzername = trim((string) ($_POST['benutzername'] ?? ''));
            $echterName = trim((string) ($_POST['echter_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $passwort = (string) ($_POST['passwort'] ?? '');
            $passwortWdh = (string) ($_POST['passwort_wiederholen'] ?? '');

            if ($benutzername === '') {
                $meldungFehler = 'Bitte einen Benutzernamen angeben.';
            } elseif (mb_strlen($passwort) < 8) {
                $meldungFehler = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            } elseif ($passwort !== $passwortWdh) {
                $meldungFehler = 'Die beiden Passwörter stimmen nicht überein.';
            } else {
                try {
                    $pdo = new PDO('sqlite:' . DB_PATH);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $pruef = $pdo->prepare('SELECT id FROM benutzer WHERE benutzername = :b');
                    $pruef->execute(['b' => $benutzername]);
                    if ($pruef->fetch() !== false) {
                        $meldungFehler = 'Dieser Benutzername ist bereits vergeben.';
                    } else {
                        $ins = $pdo->prepare(
                            'INSERT INTO benutzer (benutzername, echter_name, email, passwort_hash, rolle)
                             VALUES (:b, :n, :e, :h, \'admin\')'
                        );
                        $ins->execute([
                            'b' => $benutzername,
                            'n' => $echterName,
                            'e' => $email,
                            'h' => password_hash($passwort, PASSWORD_BCRYPT),
                        ]);

                        file_put_contents($lockDatei, 'Installiert am ' . date('Y-m-d H:i:s') . "\n");
                        $meldungErfolg = "Administrator „{$benutzername}“ wurde angelegt. Die Installation ist abgeschlossen.";
                        $stand = pruefe_installations_stand();
                        $gesperrt = true;
                    }
                } catch (\Throwable $e) {
                    $meldungFehler = 'Administrator konnte nicht angelegt werden: ' . $e->getMessage();
                }
            }
        }
    }
}

$appName = defined('APP_NAME') ? APP_NAME : 'Kunstverwaltung';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation · <?= e($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Source+Serif+4:ital,opsz@0,8..60;1,8..60&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/tokens.css">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<main class="hauptinhalt" style="max-width:640px;">
    <h1 style="font-family:var(--schrift-serif);font-style:italic;">Installation – <?= e($appName) ?></h1>

    <?php if ($meldungFehler): ?>
        <div class="flash flash--fehler"><?= e($meldungFehler) ?></div>
    <?php endif; ?>
    <?php if ($meldungErfolg): ?>
        <div class="flash flash--erfolg"><?= e($meldungErfolg) ?></div>
    <?php endif; ?>

    <?php if ($probleme !== []): ?>
        <div class="karte">
            <h3>Voraussetzungen nicht erfüllt</h3>
            <ul>
                <?php foreach ($probleme as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
            </ul>
            <p class="text-klein text-sekundaer">Seite nach Behebung der Punkte neu laden.</p>
        </div>

    <?php elseif ($gesperrt): ?>
        <div class="karte">
            <h3>Bereits installiert</h3>
            <p>Es ist bereits ein Administrator-Konto eingerichtet. Diese Installationsseite führt daher keine weiteren Aktionen mehr aus.</p>
            <p class="text-klein text-sekundaer">Aus Sicherheitsgründen empfohlen: <code>install.php</code> jetzt vom Server löschen (z. B. per FTP/Datei-Manager des Hosters).</p>
            <a href="/login.php" class="btn btn--primaer">Zum Login</a>
        </div>

    <?php elseif (!$stand['datenbank_bereit']): ?>
        <div class="karte">
            <h3>Schritt 1 von 2: Datenbank einrichten</h3>
            <p class="text-sekundaer">Legt die SQLite-Datenbank und alle benötigten Tabellen an.</p>
            <?php if ($stand['fehler']): ?>
                <div class="flash flash--fehler"><?= e($stand['fehler']) ?></div>
            <?php endif; ?>
            <form method="post" action="/install.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aktion" value="migrieren">
                <button type="submit" class="btn btn--primaer">Datenbank jetzt einrichten</button>
            </form>
        </div>

    <?php else: ?>
        <div class="karte">
            <h3>Schritt 2 von 2: Administrator anlegen</h3>
            <p class="text-sekundaer">Dieses Konto erhält vollen Zugriff (Import, Benutzerverwaltung, Backup).</p>
            <form method="post" action="/install.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aktion" value="admin_anlegen">
                <div class="feld">
                    <label for="benutzername">Benutzername</label>
                    <input type="text" id="benutzername" name="benutzername" required autofocus>
                </div>
                <div class="feldreihe">
                    <div class="feld">
                        <label for="echter_name">Echter Name</label>
                        <input type="text" id="echter_name" name="echter_name">
                    </div>
                    <div class="feld">
                        <label for="email">E-Mail</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                <div class="feldreihe">
                    <div class="feld">
                        <label for="passwort">Passwort</label>
                        <input type="password" id="passwort" name="passwort" required minlength="8">
                    </div>
                    <div class="feld">
                        <label for="passwort_wiederholen">Passwort wiederholen</label>
                        <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8">
                    </div>
                </div>
                <button type="submit" class="btn btn--primaer">Administrator anlegen</button>
            </form>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
