<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;
use App\KuenstlerBereinigung;
use App\Protokoll;

Auth::requireAdmin();
$bereinigung = KuenstlerBereinigung::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'zusammenfuehren') {
        $vorhanden = array_keys($bereinigung->namen());
        $quellen = array_values(array_intersect(array_filter((array) ($_POST['quellen'] ?? []), 'is_string'), $vorhanden));
        $ziel = trim((string) ($_POST['ziel_neu'] ?? '')) ?: trim((string) ($_POST['ziel'] ?? ''));

        if ($quellen === [] || $ziel === '' || mb_strlen($ziel) > 200) {
            Helpers::flashSet('fehler', t('kuenstler.keine_auswahl'));
        } else {
            $anzahl = $bereinigung->zusammenfuehren($quellen, $ziel);
            Protokoll::schreibe('kuenstler_zusammengefuehrt', implode(', ', $quellen) . " → {$ziel} ({$anzahl} Werke)");
            Helpers::flashSet('erfolg', t('kuenstler.zusammengefuehrt', ['n' => $anzahl, 'ziel' => $ziel]));
        }
    } elseif ($aktion === 'alias_loeschen') {
        $bereinigung->aliasLoeschen((string) ($_POST['alias'] ?? ''));
        Helpers::flashSet('erfolg', t('kuenstler.alias_entfernt'));
    }
    Helpers::redirect('/kuenstler.php');
}

render('kuenstler', [
    'titel' => t('kuenstler.titel'),
    'aktuelleSeite' => 'kuenstler',
    'namen' => $bereinigung->namen(),
    'vorschlaege' => $bereinigung->vorschlaege(),
    'aliase' => $bereinigung->aliase(),
]);
