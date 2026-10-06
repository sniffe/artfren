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

exec('git -C ' . escapeshellarg(APP_ROOT) . ' ls-files', $dateien, $code);
if ($code !== 0) {
    fwrite(STDERR, "git ls-files fehlgeschlagen\n");
    exit(1);
}

// Nicht für den Betrieb nötig: Dokumentation, Composer-Dateien, CLI-Skripte.
// Versteckte Dateien (.htaccess) prüft Wartung::fehlendeSchutzdateien() eigens.
$ausnahmen = ['README.md', 'composer.json', 'composer.lock', App\Wartung::DATEILISTE];
$dateien = array_values(array_filter($dateien, static fn(string $pfad): bool =>
    !in_array($pfad, $ausnahmen, true)
    && !str_starts_with($pfad, 'scripts/')
    && !preg_match('~(^|/)\.~', $pfad)
));
sort($dateien, SORT_STRING);

file_put_contents(APP_ROOT . '/' . App\Wartung::DATEILISTE, implode("\n", $dateien) . "\n");
echo count($dateien) . ' Dateien in ' . App\Wartung::DATEILISTE . PHP_EOL;
