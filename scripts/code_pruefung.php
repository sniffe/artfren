<?php
declare(strict_types=1);

// Prüft den Programmcode vor jeder Veröffentlichung auf Fehler, die die App
// lahmlegen, bevor sie live gehen. Läuft automatisch bei jedem Push auf GitHub
// (.github/workflows/code-pruefung.yml) und gehört zur Release-Checkliste.
//
// Aufruf: php scripts/code_pruefung.php
// Rückgabe: 0 = alles in Ordnung, 1 = Fehler gefunden.
//
// Geprüft wird:
//   1. PHP-Syntax aller Programmdateien (ohne vendor/)
//   2. Typografische Anführungszeichen dort, wo gerade stehen müssen:
//      als PHP-Begrenzer, in HTML-Attributen und als Begrenzer in JavaScript
//   3. Deutsche Anführungszeichen, die mit einem geraden Zeichen schließen („…")
//   4. Aufrufe von App-Klassen und -Methoden, die es nicht gibt
//   5. Übersetzungen: de/en vollständig, alle im Code benutzten Schlüssel vorhanden
//   6. src/dateiliste.txt passt zum aktuellen Stand (sonst meldet "System" Fehlalarme)
//   7. Versionsnummer in src/config.php und README.md stimmen überein
//   8. Inline-Skripte (onclick usw.) in Vorlagen, die die Sicherheitsrichtlinie blockiert

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// src/config.php wird absichtlich nicht geladen: Ist es selbst defekt, soll die
// Prüfung das melden statt abzustürzen. Programmdateien werden erst geladen,
// nachdem ihre Syntax geprüft ist.
define('APP_ROOT', dirname(__DIR__));
$appVersion = preg_match("/define\\('APP_VERSION',\\s*'([^']+)'\\)/", (string) @file_get_contents(APP_ROOT . '/src/config.php'), $m)
    ? $m[1] : '?';

const KP_TYPO = ['“', '”', '‘', '’'];

/** @var array<string, string[]> Prüfpunkt => Meldungen */
$fehler = [];
$hinweise = [];

$melde = static function (string $punkt, string $meldung) use (&$fehler): void {
    $fehler[$punkt][] = $meldung;
};

// ── Dateien ermitteln ────────────────────────────────────────────────────────
// Bevorzugt die Dateien unter Versionskontrolle; ohne git alle Dateien im Ordner.
$dateien = [];
exec('git -C ' . escapeshellarg(APP_ROOT) . ' ls-files 2>/dev/null', $dateien, $gitCode);
$mitGit = $gitCode === 0 && $dateien !== [];
if (!$mitGit) {
    $dateien = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $datei) {
        $rel = substr(str_replace('\\', '/', $datei->getPathname()), strlen(APP_ROOT) + 1);
        if (!preg_match('~^(\.git|data|bilder|backups)/~', $rel)) {
            $dateien[] = $rel;
        }
    }
    sort($dateien, SORT_STRING);
}
$eigene = array_values(array_filter($dateien, static fn(string $p): bool => !str_starts_with($p, 'vendor/')));
$phpDateien = array_values(array_filter($eigene, static fn(string $p): bool => str_ends_with($p, '.php')));
$jsDateien = array_values(array_filter($eigene, static fn(string $p): bool => str_ends_with($p, '.js')));

// ── 1. Syntax ────────────────────────────────────────────────────────────────
$syntaxOk = [];
foreach ($phpDateien as $pfad) {
    $ausgabe = [];
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg(APP_ROOT . '/' . $pfad) . ' 2>&1', $ausgabe, $code);
    if ($code === 0) {
        $syntaxOk[] = $pfad;
        continue;
    }
    $zeile = '';
    foreach ($ausgabe as $a) {
        if (str_contains($a, 'error')) {
            $zeile = trim(str_replace(APP_ROOT . '/', '', $a));
            break;
        }
    }
    $melde('Syntaxfehler (PHP startet diese Seite nicht)', $zeile !== '' ? $zeile : $pfad);
}

// Nur Klassen aus src/, deren Datei die Syntaxprüfung bestanden hat, dürfen
// geladen werden – eine defekte Datei würde dieses Skript selbst abbrechen.
$ladbar = [];
foreach ($syntaxOk as $pfad) {
    if (preg_match('~^src/([A-Z]\w+)\.php$~', $pfad, $m)) {
        $ladbar['App\\' . $m[1]] = APP_ROOT . '/' . $pfad;
    }
}
spl_autoload_register(static function (string $klasse) use ($ladbar): void {
    if (isset($ladbar[$klasse])) {
        require_once $ladbar[$klasse];
    }
});
$gibtEs = static fn(string $klasse): bool => isset($ladbar[$klasse]) && class_exists($klasse);
// Ist eine Datei in src/ defekt, sind Folgefehler wie "Klasse fehlt" nur Rauschen.
$srcDefekt = array_diff(
    array_filter($phpDateien, static fn(string $p): bool => str_starts_with($p, 'src/')),
    $syntaxOk
) !== [];

// ── 2.–4. Tokens untersuchen ─────────────────────────────────────────────────
$textTokens = [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML];
$kommentarTokens = [T_COMMENT, T_DOC_COMMENT];
$benutzteKeys = [];   // Schlüssel => erste Fundstelle
$pluralKeys = [];

foreach ($syntaxOk as $pfad) {
    $quelltext = (string) file_get_contents(APP_ROOT . '/' . $pfad);
    $tokens = token_get_all($quelltext);

    foreach ($tokens as $i => $token) {
        if (!is_array($token)) {
            continue;
        }
        [$typ, $inhalt, $zeile] = $token;
        $ort = "{$pfad}:{$zeile}";

        if (in_array($typ, $kommentarTokens, true) || $typ === T_WHITESPACE) {
            continue;
        }

        if (!in_array($typ, $textTokens, true)) {
            // Typografisches Zeichen mitten im PHP-Code, z. B. t(‘key’) oder “text”.
            foreach (KP_TYPO as $z) {
                if (str_contains($inhalt, $z)) {
                    $melde('Typografische Anführungszeichen als PHP-Begrenzer', "{$ort}  {$inhalt}");
                    break;
                }
            }
            continue;
        }

        // Zeilenweise, damit die Meldung die genaue Zeile nennt. Die Muster stehen
        // als \x{…}-Codes da, sonst würde sich dieses Skript selbst melden.
        foreach (explode("\n", $inhalt) as $versatz => $textzeile) {
            $hier = $pfad . ':' . ($zeile + $versatz) . '  ' . mb_strimwidth(trim($textzeile), 0, 110, '…');
            // HTML-Attribut mit typografischem Anführungszeichen: class=”liste”
            if (preg_match('/[\w-]=\s*[\x{201C}\x{201D}\x{2018}\x{2019}]/u', $textzeile)) {
                $melde('Typografische Anführungszeichen in HTML-Attributen', $hier);
            }
            // onclick="…" & Co.: Die Sicherheitsrichtlinie (script-src 'self')
            // blockiert Inline-Skripte, der Knopf täte im Browser nichts.
            if (preg_match('/\son(click|change|submit|input|load|keyup|keydown|focus|blur|mouse\w+)\s*=\s*["\']/i', $textzeile)) {
                $melde('Inline-Skripte (onclick usw.), die die Sicherheitsrichtlinie blockiert', $hier);
            }
            // Deutsches Anführungszeichen unten, oben aber gerades " statt “
            if (preg_match('/\x{201E}[^\x{201C}"\x{201E}]*"/u', $textzeile)) {
                $melde('Deutsche Anführungszeichen falsch geschlossen (Typografie)', $hier);
            }
        }
    }

    // Im Code benutzte Übersetzungsschlüssel (nur feste Schlüssel, keine Variablen)
    // Nur vollständige Schlüssel ('a.b'), nicht zusammengesetzte ('a.b_' . $x).
    if (preg_match_all("/\\bt\\(\\s*'([a-z0-9_]+\\.[a-z0-9_.]+)'\\s*[,)]/", $quelltext, $treffer, PREG_OFFSET_CAPTURE)) {
        foreach ($treffer[1] as [$key, $pos]) {
            $benutzteKeys[$key] ??= $pfad . ':' . (substr_count($quelltext, "\n", 0, $pos) + 1);
        }
    }
    if (preg_match_all("/I18n::plural\\([^,]+,\\s*'([a-z0-9_]+\\.[a-z0-9_.]+)'\\s*[,)]/", $quelltext, $treffer, PREG_OFFSET_CAPTURE)) {
        foreach ($treffer[1] as [$key, $pos]) {
            $pluralKeys[$key] ??= $pfad . ':' . (substr_count($quelltext, "\n", 0, $pos) + 1);
        }
    }

    // ── 4. App-Klassen und statische Methoden ────────────────────────────────
    if (preg_match_all('/^use App\\\\(\w+);/m', $quelltext, $treffer)) {
        foreach ($treffer[1] as $klasse) {
            if (!$gibtEs('App\\' . $klasse) && !$srcDefekt) {
                $melde('Klassen oder Methoden, die es nicht gibt', "{$pfad}  use App\\{$klasse}");
            }
        }
    }
    if (preg_match_all('/\b([A-Z]\w+)::([a-zA-Z_]\w*)\s*\(/', $quelltext, $treffer, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($treffer as $t) {
            $klasse = 'App\\' . $t[1][0];
            $methode = $t[2][0];
            if ($gibtEs($klasse) && !method_exists($klasse, $methode)) {
                $zeile = substr_count($quelltext, "\n", 0, $t[0][1]) + 1;
                $melde('Klassen oder Methoden, die es nicht gibt', "{$pfad}:{$zeile}  {$t[1][0]}::{$methode}()");
            }
        }
    }
}

// JavaScript: typografisches Zeichen direkt an Code-Zeichen, z. B. (“text”) oder = ‘a’
foreach ($jsDateien as $pfad) {
    foreach (file(APP_ROOT . '/' . $pfad) ?: [] as $nr => $zeile) {
        $code = preg_replace('~^\s*(//|\*|/\*).*$~', '', $zeile);
        if (preg_match('/[(\[=,:+]\s*[“‘]|[”’]\s*[)\];,+]/u', (string) $code)) {
            $melde('Typografische Anführungszeichen als JavaScript-Begrenzer', $pfad . ':' . ($nr + 1) . '  ' . trim($zeile));
        }
    }
}

// ── 5. Übersetzungen ─────────────────────────────────────────────────────────
$ausgabe = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(APP_ROOT . '/scripts/i18n_pruefung.php') . ' 2>&1', $ausgabe, $code);
if ($code !== 0) {
    foreach ($ausgabe as $a) {
        if (str_starts_with($a, '  ')) {
            $melde('Übersetzungen de/en nicht deckungsgleich', trim($a));
        }
    }
}
$de = in_array('lang/de.php', $syntaxOk, true) ? require APP_ROOT . '/lang/de.php' : [];
foreach ($benutzteKeys as $key => $ort) {
    if (!array_key_exists($key, $de) && !array_key_exists($key . '.one', $de)) {
        $melde('Übersetzungsschlüssel fehlen (Anzeige zeigt sonst den Schlüssel)', "{$ort}  {$key}");
    }
}
foreach ($pluralKeys as $key => $ort) {
    if (!array_key_exists($key . '.one', $de) || !array_key_exists($key . '.other', $de)) {
        $melde('Übersetzungsschlüssel fehlen (Anzeige zeigt sonst den Schlüssel)', "{$ort}  {$key}.one/.other");
    }
}

// ── 6. Dateiliste ────────────────────────────────────────────────────────────
if ($mitGit && $gibtEs('App\\Wartung')) {
    $soll = App\Wartung::erzeugeDateiliste($dateien);
    $ist = (string) @file_get_contents(APP_ROOT . '/' . App\Wartung::DATEILISTE);
    if (str_replace("\r\n", "\n", $ist) !== $soll) {
        $melde('Dateiliste veraltet', App\Wartung::DATEILISTE . ' neu erzeugen: php scripts/dateiliste.php (danach committen)');
    }
} elseif (!$mitGit) {
    $hinweise[] = 'Ohne git keine Prüfung der Dateiliste möglich.';
}

// ── 7. Versionsnummer ────────────────────────────────────────────────────────
$readme = (string) @file_get_contents(APP_ROOT . '/README.md');
if (substr_count($readme, 'Version ' . $appVersion . '.') < 2) {
    $melde('Versionsnummer uneinheitlich', 'src/config.php sagt ' . $appVersion . ', README.md nennt diese Version nicht in beiden Sprachen ("Version ' . $appVersion . '.")');
}

// ── Ausgabe ──────────────────────────────────────────────────────────────────
echo 'Codeprüfung Version ' . $appVersion . ': ' . count($phpDateien) . ' PHP-Dateien, ' . count($jsDateien) . ' JavaScript-Dateien' . PHP_EOL;
foreach ($hinweise as $h) {
    echo 'Hinweis: ' . $h . PHP_EOL;
}
if ($fehler === []) {
    echo 'OK – keine Fehler gefunden.' . PHP_EOL;
    exit(0);
}
$anzahl = array_sum(array_map('count', $fehler));
echo PHP_EOL . $anzahl . ' Fehler gefunden:' . PHP_EOL;
foreach ($fehler as $punkt => $meldungen) {
    echo PHP_EOL . '## ' . $punkt . ' (' . count($meldungen) . ')' . PHP_EOL;
    foreach (array_slice($meldungen, 0, 40) as $m) {
        echo '  ' . $m . PHP_EOL;
    }
    if (count($meldungen) > 40) {
        echo '  … und ' . (count($meldungen) - 40) . ' weitere' . PHP_EOL;
    }
}
exit(1);
