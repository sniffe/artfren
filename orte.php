<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;
use App\OrtBereinigung;
use App\Protokoll;

Auth::requireAdmin();
$bereinigung = OrtBereinigung::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'zusammenfuehren') {
        $vorhanden = array_keys($bereinigung->orte());
        $quellen = array_values(array_intersect(array_filter((array) ($_POST['quellen'] ?? []), 'is_string'), $vorhanden));
        $ziel = trim((string) ($_POST['ziel_neu'] ?? '')) ?: trim((string) ($_POST['ziel'] ?? ''));

        if ($quellen === [] || $ziel === '' || mb_strlen($ziel) > 200) {
            Helpers::flashSet('fehler', 'Bitte Orte auswählen und einen Zielnamen angeben.');
        } else {
            $anzahl = $bereinigung->zusammenfuehren($quellen, $ziel);
            Protokoll::schreibe('orte_zusammengefuehrt', implode(', ', $quellen) . " → {$ziel} ({$anzahl} Werke)");
            Helpers::flashSet('erfolg', "{$anzahl} Werke auf „{$ziel}“ gesetzt. Die alten Schreibweisen werden bei künftigen Importen automatisch umgesetzt.");
        }
    } elseif ($aktion === 'alias_loeschen') {
        $bereinigung->aliasLoeschen((string) ($_POST['alias'] ?? ''));
        Helpers::flashSet('erfolg', 'Zuordnung entfernt.');
    }
    Helpers::redirect('/orte.php');
}

render('orte', [
    'titel' => 'Orte bereinigen',
    'aktuelleSeite' => 'import',
    'orte' => $bereinigung->orte(),
    'vorschlaege' => $bereinigung->vorschlaege(),
    'aliase' => $bereinigung->aliase(),
]);
