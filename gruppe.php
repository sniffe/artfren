<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

$benutzer = Auth::requireLogin();
$repo = GruppeRepository::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    Helpers::checkCsrf();

    if (($_POST['aktion'] ?? '') === 'mitglieder_speichern') {
        $gruppe = $repo->finde((int) ($_POST['gruppe_id'] ?? 0));
        if ($gruppe === null) {
            Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
        }
        $repo->setzeMitglieder((int) $gruppe['id'], Helpers::idListe($_POST['werk_ids'] ?? []));
        $anzahl = count(WerkRepository::neu()->mitgliedIds((int) $gruppe['id']));
        Protokoll::schreibe('gruppe_mitglieder', "„{$gruppe['name']}“: jetzt {$anzahl} Werke");
        Helpers::flashSet('erfolg', "Mitglieder der Gruppe wurden aktualisiert ({$anzahl} Werke).");
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id'] . '&mitglieder_gespeichert=1');
    }
    Helpers::redirect('/gruppen.php');
}

$id = (int) ($_GET['id'] ?? 0);
$gruppe = $repo->finde($id);
if ($gruppe === null || !Auth::darfGruppeSehen($benutzer, $id)) {
    Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
}

$istAdmin = Auth::isAdmin($benutzer);
$werke = WerkRepository::neu()->fuerGruppe($id);
render('gruppe_ansicht', [
    'titel' => $gruppe['name'],
    'aktuelleSeite' => 'gruppen',
    'breit' => true,
    'gruppe' => $gruppe,
    'werke' => $werke,
    'bilderAnzahl' => count(\App\Export::bilderZuWerken($werke)['dateien']),
    'istAdmin' => $istAdmin,
    'freigegebenFuer' => $istAdmin ? $repo->freigegebenFuer($id) : [],
    'auswahlVerwerfen' => array_filter([
        isset($_GET['neu']) ? 'auswahl_neu' : null,
        isset($_GET['mitglieder_gespeichert']) ? 'auswahl_gruppe_' . $id : null,
    ]),
]);
