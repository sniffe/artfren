<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\GruppeRepository;
use App\Helpers;
use App\WerkRepository;

Auth::requireAdmin();
$werkRepo = WerkRepository::neu();

$filter = [
    'q'            => trim((string) ($_GET['q'] ?? '')),
    'ort'          => trim((string) ($_GET['ort'] ?? '')),
    'maler'        => trim((string) ($_GET['maler'] ?? '')),
    'status'       => (string) ($_GET['status'] ?? ''),
    'web_freigabe' => (string) ($_GET['web_freigabe'] ?? ''),
    'ohne_bild'    => (string) ($_GET['ohne_bild'] ?? ''),
];
$sortierung = isset(WerkRepository::SORTIERUNGEN[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : 'ort';
$absteigend = ($_GET['richtung'] ?? '') === 'ab';
$proSeite = 50;

$gruppe = null;
if (($_GET['gruppe_id'] ?? '') !== '') {
    $gruppe = GruppeRepository::neu()->finde((int) $_GET['gruppe_id']);
    if ($gruppe === null) {
        Helpers::flashSet('fehler', t('gruppen.nicht_gefunden'));
        Helpers::redirect('/gruppen.php');
    }
}

$gesamtAnzahl = $werkRepo->zaehle($filter);
$seitenAnzahl = max(1, (int) ceil($gesamtAnzahl / $proSeite));
$seite = min(max(1, (int) ($_GET['seite'] ?? 1)), $seitenAnzahl);

render('werke_liste', [
    'titel' => $gruppe ? t('werke.fuer_gruppe', ['name' => $gruppe['name']]) : t('werke.titel'),
    'aktuelleSeite' => 'werke',
    'breit' => true,
    'werke' => $werkRepo->suche($filter, $sortierung, $absteigend, $proSeite, ($seite - 1) * $proSeite),
    'trefferIds' => $werkRepo->ids($filter),
    'orte' => $werkRepo->orte(),
    'malerListe' => $werkRepo->maler(),
    'filter' => $filter,
    'sortierung' => $sortierung,
    'absteigend' => $absteigend,
    'seite' => $seite,
    'seitenAnzahl' => $seitenAnzahl,
    'gesamtAnzahl' => $gesamtAnzahl,
    'gruppe' => $gruppe,
    'aktuelleMitglieder' => $gruppe ? $werkRepo->mitgliedIds((int) $gruppe['id']) : [],
    'freieBilder' => \App\BildUpload::unzugeordnet(),
]);
