<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;
use App\ImportFehler;
use App\Protokoll;
use App\TabellenImport;

Auth::requireAdmin();
$pdo = Database::get();

if (!is_dir(IMPORT_TMP_PATH)) {
    mkdir(IMPORT_TMP_PATH, 0755, true);
}
TabellenImport::raeumeAuf();

/** Nur selbst erzeugte Zwischendateinamen zulassen (kein Pfad aus dem Formular). */
function import_pfad(mixed $name): ?string
{
    if (!is_string($name) || !preg_match('/^import_[a-f0-9]{32}\.csv$/', $name)) {
        return null;
    }
    $pfad = IMPORT_TMP_PATH . '/' . $name;
    return is_file($pfad) ? $pfad : null;
}

function zeige_mapping(string $tmpName, array $mapping, ?string $fehler = null, array $info = []): never
{
    $vorschau = TabellenImport::vorschau(IMPORT_TMP_PATH . '/' . $tmpName);
    render('import_mapping', array_merge($vorschau, $info, [
        'titel' => 'Import – Spalten zuordnen',
        'aktuelleSeite' => 'import',
        'breit' => true,
        'mapping' => $mapping ?: TabellenImport::automatischesMapping($vorschau['header']),
        'tmp_datei' => $tmpName,
        'fehler' => $fehler,
    ]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render('import_upload', [
        'titel' => 'Import',
        'aktuelleSeite' => 'import',
        'maxGroesse' => min(TabellenImport::MAX_DATEIGROESSE, \App\Bilder::alsBytes((string) ini_get('upload_max_filesize'))),
    ]);
    exit;
}

Helpers::checkCsrf();
$aktion = (string) ($_POST['aktion'] ?? '');

if ($aktion === 'hochladen') {
    $datei = $_FILES['datei'] ?? null;
    if (!is_array($datei) || ($datei['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $fehler = ($datei['error'] ?? null) === UPLOAD_ERR_INI_SIZE ? 'Die Datei ist größer als vom Server erlaubt.' : 'Es wurde keine Datei hochgeladen.';
        Helpers::flashSet('fehler', $fehler);
        Helpers::redirect('/import.php');
    }
    if ($datei['size'] > TabellenImport::MAX_DATEIGROESSE) {
        Helpers::flashSet('fehler', 'Die Datei ist zu groß (höchstens 20 MB).');
        Helpers::redirect('/import.php');
    }

    $tmpName = 'import_' . bin2hex(random_bytes(16)) . '.csv';
    try {
        $info = TabellenImport::normalisiere($datei['tmp_name'], (string) $datei['name'], IMPORT_TMP_PATH . '/' . $tmpName);
    } catch (ImportFehler $e) {
        @unlink(IMPORT_TMP_PATH . '/' . $tmpName);
        Helpers::flashSet('fehler', $e->getMessage());
        Helpers::redirect('/import.php');
    }
    zeige_mapping($tmpName, [], null, ['info' => $info, 'dateiname' => (string) $datei['name']]);
}

if ($aktion === 'abgleich' || $aktion === 'uebernehmen') {
    $pfad = import_pfad($_POST['tmp_datei'] ?? null);
    if ($pfad === null) {
        Helpers::flashSet('fehler', 'Die Import-Datei ist nicht mehr verfügbar. Bitte erneut hochladen.');
        Helpers::redirect('/import.php');
    }
    $tmpName = basename($pfad);
    $mapping = TabellenImport::pruefeMapping($_POST['mapping'] ?? []);

    $mappingFehler = TabellenImport::mappingFehler($mapping);
    if ($mappingFehler !== null) {
        zeige_mapping($tmpName, $mapping, $mappingFehler);
    }

    $gelesen = TabellenImport::leseAlleZeilen($pfad, $mapping);
    $zeilen = $gelesen['zeilen'];
    $aliasErsetzt = TabellenImport::wendeOrtAliaseAn($pdo, $zeilen);
    $gemappt = array_values(array_filter($mapping));
    $abgleich = TabellenImport::berechneAbgleich($pdo, $zeilen, $gemappt);

    if ($aktion === 'abgleich') {
        render('import_abgleich', [
            'titel' => 'Import – Abgleich',
            'aktuelleSeite' => 'import',
            'abgleich' => $abgleich,
            'tmp_datei' => $tmpName,
            'mapping' => $mapping,
            'gesamtzeilen' => count($zeilen),
            'uebersprungen' => $gelesen['uebersprungen'],
            'ungueltigeDateinamen' => $gelesen['ungueltigeDateinamen'],
            'aliasErsetzt' => $aliasErsetzt,
            'nichtZugeordnet' => array_diff(array_keys(array_filter(TabellenImport::ZIELFELDER, static fn($k) => $k !== '', ARRAY_FILTER_USE_KEY)), $gemappt),
        ]);
        exit;
    }

    $ueberschreiben = !empty($_POST['bearbeitete_ueberschreiben']);
    TabellenImport::uebernehmen($pdo, $abgleich, $gemappt, $ueberschreiben);
    @unlink($pfad);

    $zusammenfassung = sprintf('%d neu, %d aktualisiert, %d unverändert, %d ohne Bilddatei, %d bearbeitete Werke %s',
        count($abgleich['neu']), count($abgleich['geaendert']), $abgleich['unveraendert'], count($abgleich['fehlerBild']),
        count($abgleich['geschuetzt']), $ueberschreiben ? 'überschrieben' : 'beibehalten');
    Protokoll::schreibe('import', $zusammenfassung);

    render('import_ergebnis', [
        'titel' => 'Import abgeschlossen',
        'aktuelleSeite' => 'import',
        'abgleich' => $abgleich,
        'bearbeiteteUeberschrieben' => $ueberschreiben,
    ]);
    exit;
}

Helpers::redirect('/import.php');
