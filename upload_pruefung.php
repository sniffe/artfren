<?php
declare(strict_types=1);

// Wird von jeder Seite als Erstes geladen – noch vor src/. Fehlen nach einem
// Upload Teile des Programms (abgebrochene FTP-Übertragung, noch laufender
// Upload, Ordner vergessen), erscheint statt eines PHP-Fehlers eine
// verständliche Seite, die sagt, was fehlt.
//
// Absichtlich ohne jede Abhängigkeit, damit sie auch dann funktioniert, wenn
// src/ oder vendor/ gar nicht vorhanden sind.

ini_set('display_errors', '0');

/** Schlüsseldateien je Ordner; wird bei jedem Seitenaufruf geprüft (schnell). */
const KV_PFLICHTDATEIEN = [
    'src/bootstrap.php',
    'src/bootstrap_public.php',
    'src/config.php',
    'src/Auth.php',
    'src/Database.php',
    'src/Helpers.php',
    'src/Migration.php',
    'src/Einstellungen.php',
    'src/Wartung.php',
    'src/Felder.php',
    'src/WebGruppe.php',
    'templates/layout.php',
    'templates/fehler.php',
    'templates/web_layout.php',
    'assets/tokens.css',
    'assets/style.css',
    'assets/app.js',
    'assets/icons.svg',
    'assets/looks.css',
    'migrations/001_basis.sql',
    'vendor/autoload.php',
    'vendor/composer/autoload_real.php',
    'vendor/composer/autoload_static.php',
    'vendor/composer/ClassLoader.php',
];

const KV_ORDNER_TEXTE = [
    '' => 'Hauptordner',
    'src' => 'Programmcode',
    'templates' => 'Seitenvorlagen',
    'assets' => 'Gestaltung und Skripte',
    'migrations' => 'Datenbank-Updates',
    'vendor' => 'Zusatzbibliotheken – der größte Ordner mit über tausend Dateien',
];

/** Wandelt einen absoluten Pfad in einen Pfad relativ zum Programmordner um (nie Serverpfade anzeigen). */
function kv_relativer_pfad(string $pfad): ?string
{
    $basis = rtrim(str_replace('\\', '/', __DIR__), '/') . '/';
    $pfad = str_replace('\\', '/', $pfad);
    // "src/../vendor/x.php" → "vendor/x.php"
    $teile = [];
    foreach (explode('/', $pfad) as $teil) {
        if ($teil === '..') {
            array_pop($teile);
        } elseif ($teil !== '.') {
            $teile[] = $teil;
        }
    }
    $pfad = implode('/', $teile);
    return str_starts_with($pfad, $basis) ? substr($pfad, strlen($basis)) : null;
}

/**
 * Zeigt die Hinweisseite "Programm unvollständig hochgeladen" und beendet.
 *
 * @param string[] $fehlend Pfade relativ zum Programmordner
 */
function kv_upload_unvollstaendig(array $fehlend): never
{
    $nachOrdner = [];
    foreach ($fehlend as $pfad) {
        $ordner = str_contains($pfad, '/') ? strstr($pfad, '/', true) : '';
        $nachOrdner[$ordner][] = $pfad;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(503);
        // Eine evtl. schon gesetzte, strengere CSP würde das Inline-CSS blockieren.
        header_remove('Content-Security-Policy');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'");
        header('Retry-After: 60');
        header('Cache-Control: no-store');
        header('Content-Type: text/html; charset=UTF-8');
    }

    $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $liste = '';
    foreach ($nachOrdner as $ordner => $pfade) {
        $name = $ordner === '' ? 'Hauptordner' : $ordner . '/';
        $text = KV_ORDNER_TEXTE[$ordner] ?? '';
        $beispiele = array_slice($pfade, 0, 5);
        $liste .= '<li><strong>' . $e($name) . '</strong>' . ($text !== '' && $ordner !== '' ? ' (' . $e($text) . ')' : '')
            . '<br><small>fehlt z. B.: ' . $e(implode(', ', $beispiele)) . (count($pfade) > 5 ? ' …' : '') . '</small></li>';
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Programm unvollständig hochgeladen</title>
<style>
:root { --hg: #F7F7F5; --fl: #FFFFFF; --tx: #1C1B1A; --sk: #5f5d59; --ak: #7A2A38; --rd: #e3e1dc; }
@media (prefers-color-scheme: dark) { :root { --hg: #1B1B1D; --fl: #242426; --tx: #EDECEA; --sk: #a9a7a2; --ak: #C97C89; --rd: #3a3a3d; } }
body { margin: 0; background: var(--hg); color: var(--tx); font: 16px/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
main { max-width: 640px; margin: 48px auto; padding: 0 16px; }
.karte { background: var(--fl); border: 1px solid var(--rd); border-top: 4px solid var(--ak); border-radius: 8px; padding: 24px 28px; }
h1 { font-size: 1.35rem; margin: 0 0 12px; }
li { margin-bottom: 8px; }
small, .leise { color: var(--sk); }
code { font-size: .92em; }
</style>
</head>
<body>
<main>
<div class="karte">
<h1>Das Programm ist noch nicht vollständig hochgeladen</h1>
<p>Auf dem Server fehlen Dateien in diesen Ordnern:</p>
<ul>{$liste}</ul>
<p><strong>So geht es weiter:</strong></p>
<ol>
<li>Prüfen, ob das Hochladen noch läuft. Vor allem <code>vendor/</code> braucht oft 10–30 Minuten. Danach diese Seite neu laden.</li>
<li>Läuft nichts mehr: die genannten Ordner aus dem heruntergeladenen Paket noch einmal in den Programmordner hochladen und „Überschreiben“ wählen. Im FTP-Programm (z. B. FileZilla) auch unter „Fehlgeschlagene Übertragungen“ nachsehen.</li>
<li>Wichtig: den <em>Inhalt</em> des heruntergeladenen Ordners hochladen, nicht den Ordner selbst – sonst landet alles eine Ebene zu tief.</li>
</ol>
<p class="leise">Bereits gespeicherte Werke, Bilder und Benutzer bleiben davon unberührt.</p>
</div>
</main>
</body>
</html>
HTML;
    exit;
}

/** Pfad der fehlenden Programmdatei aus einer "Failed opening required"-Meldung, sonst null. */
function kv_fehlende_datei_aus_meldung(string $meldung): ?string
{
    if (!preg_match("/Failed opening required '([^']+)'/", $meldung, $m) || is_file($m[1])) {
        return null;
    }
    return kv_relativer_pfad($m[1]);
}

/**
 * Erkennt Fehler, die auf eine fehlende Programmdatei zurückgehen: ein
 * gescheitertes require oder eine Klasse, deren Datei laut Autoloader fehlt.
 *
 * @param array<string, string[]> $psr4 Präfixe des Composer-Autoloaders
 */
function kv_fehlende_datei(\Throwable $e, array $psr4 = []): ?string
{
    if (($pfad = kv_fehlende_datei_aus_meldung($e->getMessage())) !== null) {
        return $pfad;
    }
    if (!$e instanceof \Error || !preg_match('/^(?:Class|Interface|Trait|Enum) "([^"]+)" not found/', $e->getMessage(), $m)) {
        return null;
    }
    $klasse = ltrim($m[1], '\\');
    foreach ($psr4 as $praefix => $ordner) {
        if (str_starts_with($klasse, $praefix) && isset($ordner[0])) {
            $datei = $ordner[0] . '/' . str_replace('\\', '/', substr($klasse, strlen($praefix))) . '.php';
            return is_file($datei) ? null : kv_relativer_pfad($datei);
        }
    }
    return null;
}

// Unter PHP 8.1/8.2 bricht ein gescheitertes require mit einem fatalen Fehler
// ab statt mit einer Exception. Auch dann die verständliche Seite zeigen.
register_shutdown_function(static function (): void {
    $fehler = error_get_last();
    if ($fehler !== null && in_array($fehler['type'], [E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR], true)
        && ($pfad = kv_fehlende_datei_aus_meldung($fehler['message'])) !== null) {
        kv_upload_unvollstaendig([$pfad]);
    }
});

// Bis src/bootstrap.php seinen eigenen Fehlerbehandler setzt.
set_exception_handler(static function (\Throwable $e): void {
    $pfad = kv_fehlende_datei($e);
    if ($pfad !== null) {
        kv_upload_unvollstaendig([$pfad]);
    }
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Es ist ein Fehler aufgetreten.';
});

(static function (): void {
    $fehlend = [];
    foreach (KV_PFLICHTDATEIEN as $pfad) {
        if (!is_file(__DIR__ . '/' . $pfad)) {
            $fehlend[] = $pfad;
        }
    }
    if ($fehlend !== []) {
        kv_upload_unvollstaendig($fehlend);
    }
})();
