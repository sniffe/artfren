<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

$benutzer = Auth::requireAdmin();
$werkRepo = WerkRepository::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'wiederherstellen') {
        $id = (int) ($_POST['werk_id'] ?? 0);
        $werk = $werkRepo->findeImPapierkorb($id);
        if ($werk !== null) {
            $werkRepo->wiederherstellen($id);
            Protokoll::schreibe('werk_wiederhergestellt', 'ID ' . $id . ': ' . ($werk['titel'] ?? ''), $benutzer);
            Helpers::flashSet('erfolg', 'Werk „' . ($werk['titel'] ?? 'Unbenannt') . '" wurde wiederhergestellt.');
        }
        Helpers::redirect('/papierkorb.php');
    }

    if ($aktion === 'endgueltig_loeschen') {
        $id = (int) ($_POST['werk_id'] ?? 0);
        $werk = $werkRepo->findeImPapierkorb($id);
        if ($werk !== null) {
            $werkRepo->endgueltigLoeschen($id, $benutzer['benutzername']);
            Protokoll::schreibe('werk_endgueltig_geloescht', 'ID ' . $id . ': ' . ($werk['titel'] ?? ''), $benutzer);
            Helpers::flashSet('erfolg', 'Werk „' . ($werk['titel'] ?? 'Unbenannt') . '" wurde endgültig gelöscht.');
        }
        Helpers::redirect('/papierkorb.php');
    }

    // Bulk: mehrere Werke aus der Werkliste in den Papierkorb
    if ($aktion === 'bulk_papierkorb') {
        $ids = array_map('intval', (array) ($_POST['werk_ids'] ?? []));
        $ids = array_values(array_filter($ids, static fn($id) => $id > 0));
        foreach ($ids as $id) {
            $werkRepo->inDenPapierkorb($id, $benutzer['benutzername']);
        }
        if ($ids !== []) {
            $anzahl = count($ids);
            Protokoll::schreibe('werk_papierkorb', $anzahl . ' Werke in den Papierkorb verschoben', $benutzer);
            Helpers::flashSet('erfolg', $anzahl . ' ' . ($anzahl === 1 ? 'Werk' : 'Werke') . ' in den Papierkorb verschoben.');
        }
        Helpers::redirect('/werke.php');
    }

    Helpers::redirect('/papierkorb.php');
}

$werke = $werkRepo->papierkorbListe();

render('papierkorb', [
    'titel'         => 'Papierkorb',
    'aktuelleSeite' => 'papierkorb',
    'werke'         => $werke,
]);
