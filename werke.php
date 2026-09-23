<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireAdmin();
$pdo = Database::get();

$q = trim((string) ($_GET['q'] ?? ''));
$ortFilter = trim((string) ($_GET['ort'] ?? ''));
$seite = max(1, (int) ($_GET['seite'] ?? 1));
$proSeite = 50;

$gruppeId = isset($_GET['gruppe_id']) && $_GET['gruppe_id'] !== '' ? (int) $_GET['gruppe_id'] : null;
$gruppe = null;
if ($gruppeId !== null) {
    $stmt = $pdo->prepare('SELECT * FROM gruppen WHERE id = :id');
    $stmt->execute(['id' => $gruppeId]);
    $gruppe = $stmt->fetch();
    if ($gruppe === false) {
        Helpers::flashSet('fehler', 'Gruppe wurde nicht gefunden.');
        Helpers::redirect('/gruppen.php');
    }
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(maler LIKE :q OR titel LIKE :q OR ort LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($ortFilter !== '') {
    $where[] = 'ort = :ort';
    $params['ort'] = $ortFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$anzahlStmt = $pdo->prepare("SELECT COUNT(*) AS n FROM kunstwerke {$whereSql}");
$anzahlStmt->execute($params);
$gesamtAnzahl = (int) $anzahlStmt->fetch()['n'];
$seitenAnzahl = max(1, (int) ceil($gesamtAnzahl / $proSeite));
$seite = min($seite, $seitenAnzahl);
$offset = ($seite - 1) * $proSeite;

$sql = "SELECT k.*, (
            SELECT dateiname FROM bilder b
            WHERE b.kunstwerk_id = k.id
            ORDER BY ist_hauptbild DESC, sortierung ASC LIMIT 1
        ) AS bild_dateiname
        FROM kunstwerke k
        {$whereSql}
        ORDER BY ort, maler, titel
        LIMIT {$proSeite} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$werke = $stmt->fetchAll();

$orte = $pdo->query('SELECT DISTINCT ort FROM kunstwerke WHERE ort IS NOT NULL AND ort <> \'\' ORDER BY ort')->fetchAll();

$aktuelleMitglieder = [];
if ($gruppeId !== null) {
    $stmt = $pdo->prepare('SELECT kunstwerk_id FROM gruppe_kunstwerk WHERE gruppe_id = :id');
    $stmt->execute(['id' => $gruppeId]);
    $aktuelleMitglieder = array_map('intval', array_column($stmt->fetchAll(), 'kunstwerk_id'));
}

render('werke_liste', [
    'titel' => $gruppe ? 'Werke für Gruppe „' . $gruppe['name'] . '“ auswählen' : 'Werke',
    'aktuelleSeite' => 'werke',
    'breit' => true,
    'werke' => $werke,
    'orte' => $orte,
    'q' => $q,
    'ortFilter' => $ortFilter,
    'seite' => $seite,
    'seitenAnzahl' => $seitenAnzahl,
    'gesamtAnzahl' => $gesamtAnzahl,
    'gruppe' => $gruppe,
    'aktuelleMitglieder' => $aktuelleMitglieder,
]);
