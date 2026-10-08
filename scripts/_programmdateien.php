<?php
declare(strict_types=1);

// Gemeinsame Hilfsfunktion für scripts/dateiliste.php und scripts/code_pruefung.php.
//
// Liefert die Dateien unter Versionskontrolle – ohne die, die beim Download
// als ZIP von GitHub fehlen. Bibliotheken in vendor/ schließen per
// .gitattributes ("export-ignore") ihre Tests, Doku-Konfiguration und
// Entwicklerwerkzeuge aus. Stünden diese in der Dateiliste, meldete "System"
// nach einem Update aus dem GitHub-ZIP Fehlalarme ("140 Dateien fehlen").

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** @return string[]|null Pfade relativ zum Programmordner, null ohne git */
function kv_programmdateien(string $wurzel): ?array
{
    $dateien = [];
    exec('git -C ' . escapeshellarg($wurzel) . ' ls-files 2>/dev/null', $dateien, $code);
    if ($code !== 0 || $dateien === []) {
        return null;
    }

    // export-ignore abfragen: eine Zeile je Pfad, "pfad: export-ignore: set|unspecified".
    // Abgefragt werden Dateien UND ihre Ordner – Regeln wie "/unitTests
    // export-ignore" gelten für den Ordner, nicht für die Dateien darin. Ordner
    // werden mit "/" am Ende abgefragt, sonst greifen Regeln wie "/Build/" nicht.
    $pfade = [];
    foreach ($dateien as $datei) {
        $pfade[$datei] = true;
        for ($ordner = dirname($datei); $ordner !== '.' && $ordner !== ''; $ordner = dirname($ordner)) {
            $pfade[$ordner . '/'] = true;
        }
    }
    $befehl = 'git -C ' . escapeshellarg($wurzel) . ' check-attr --stdin export-ignore';
    $prozess = proc_open($befehl, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $rohre);
    if (!is_resource($prozess)) {
        return $dateien;
    }
    fwrite($rohre[0], implode("\n", array_keys($pfade)) . "\n");
    fclose($rohre[0]);
    $ausgabe = (string) stream_get_contents($rohre[1]);
    fclose($rohre[1]);
    fclose($rohre[2]);
    proc_close($prozess);

    $ausgeschlossen = [];
    foreach (explode("\n", $ausgabe) as $zeile) {
        if (str_ends_with($zeile, ': export-ignore: set')) {
            $ausgeschlossen[substr($zeile, 0, -strlen(': export-ignore: set'))] = true;
        }
    }
    return array_values(array_filter($dateien, static function (string $p) use ($ausgeschlossen): bool {
        for ($pfad = $p; $pfad !== '.' && $pfad !== ''; $pfad = dirname($pfad)) {
            if (isset($ausgeschlossen[$pfad]) || isset($ausgeschlossen[$pfad . '/'])) {
                return false;
            }
        }
        return true;
    }));
}
