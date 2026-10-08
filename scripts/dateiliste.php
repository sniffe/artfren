<?php
declare(strict_types=1);

// Erzeugt src/dateiliste.txt: alle Programmdateien dieser Version. Damit
// prüft "System", ob ein Update vollständig hochgeladen wurde.
// Vor jeder neuen Version ausführen: php scripts/dateiliste.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config.php';
require APP_ROOT . '/src/Wartung.php';

require __DIR__ . '/_programmdateien.php';

// Dateien wie im GitHub-Download (ohne export-ignore der Bibliotheken).
$dateien = kv_programmdateien(APP_ROOT);
if ($dateien === null) {
    fwrite(STDERR, "git ls-files fehlgeschlagen\n");
    exit(1);
}

// Auswahl und Prüfsummen legt Wartung::erzeugeDateiliste() fest; dieselbe
// Funktion nutzt scripts/code_pruefung.php, um eine veraltete Liste zu erkennen.
// Versteckte Dateien (.htaccess) prüft Wartung::fehlendeSchutzdateien() eigens.
$inhalt = App\Wartung::erzeugeDateiliste($dateien);

file_put_contents(APP_ROOT . '/' . App\Wartung::DATEILISTE, $inhalt);
echo substr_count($inhalt, "\n") . ' Dateien mit Prüfsumme in ' . App\Wartung::DATEILISTE . PHP_EOL;
