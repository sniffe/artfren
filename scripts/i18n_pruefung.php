<?php
declare(strict_types=1);

// Prüft die Vollständigkeit und Konsistenz der Übersetzungsdateien.
// Ausführen: php scripts/i18n_pruefung.php
// Rückgabe: 0 = alles in Ordnung, 1 = Fehler gefunden.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config.php';

$langDir = APP_ROOT . '/lang';
$de = (array) (require $langDir . '/de.php');
$en = (array) (require $langDir . '/en.php');

$fehler = [];
$warnungen = [];

// ── 1. Keys in de, aber nicht in en ─────────────────────────────────────────
$nurInDe = array_diff_key($de, $en);
foreach (array_keys($nurInDe) as $key) {
    $fehler[] = "Fehlt in en.php: $key";
}

// ── 2. Keys in en, aber nicht in de ─────────────────────────────────────────
$nurInEn = array_diff_key($en, $de);
foreach (array_keys($nurInEn) as $key) {
    $fehler[] = "Fehlt in de.php: $key";
}

// ── 3. Platzhalter-Konsistenz für gemeinsame Keys ────────────────────────────
/** @param string $text */
function platzhalter(string $text): array
{
    preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $text, $m);
    $gefunden = $m[1];
    sort($gefunden);
    return array_unique($gefunden);
}

$gemeinsam = array_intersect_key($de, $en);
foreach ($gemeinsam as $key => $deText) {
    $enText = $en[$key];
    $dePH = platzhalter((string) $deText);
    $enPH = platzhalter((string) $enText);

    $nurDE = array_diff($dePH, $enPH);
    $nurEN = array_diff($enPH, $dePH);

    if ($nurDE !== []) {
        $fehler[] = "Platzhalter in de aber nicht en für \"$key\": {" . implode('}, {', $nurDE) . '}';
    }
    if ($nurEN !== []) {
        $fehler[] = "Platzhalter in en aber nicht de für \"$key\": {" . implode('}, {', $nurEN) . '}';
    }
}

// ── Ausgabe ──────────────────────────────────────────────────────────────────
$deCount = count($de);
$enCount = count($en);
echo "de.php: $deCount Keys, en.php: $enCount Keys" . PHP_EOL;

if ($fehler === []) {
    echo 'OK – alle Keys und Platzhalter stimmen überein.' . PHP_EOL;
    exit(0);
}

echo count($fehler) . ' Fehler gefunden:' . PHP_EOL;
foreach ($fehler as $f) {
    echo '  ' . $f . PHP_EOL;
}
exit(1);
