<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;

$benutzer = Auth::requireLogin();
$repo = GruppeRepository::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'anlegen') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $werkIds = Helpers::idListe($_POST['werk_ids'] ?? []);

        if ($name === '' || mb_strlen($name) > 200) {
            Helpers::flashSet('fehler', t('gruppe_neu.name_leer'));
            Helpers::redirect('/gruppe_neu.php');
        }
        if ($werkIds === []) {
            Helpers::flashSet('fehler', t('gruppe_neu.werk_leer'));
            Helpers::redirect('/gruppe_neu.php');
        }

        $neueId = $repo->anlegen($name, (int) $benutzer['id'], $werkIds);
        $anzahl = count(\App\WerkRepository::neu()->mitgliedIds($neueId));
        Protokoll::schreibe('gruppe_angelegt', “„{$name}” mit {$anzahl} Werken”);
        Helpers::flashSet('erfolg', t('gruppe_neu.angelegt_mit', ['n' => $anzahl]));
        Helpers::redirect('/gruppe.php?id=' . $neueId . '&neu=1');
    }

    $gruppe = $repo->finde((int) ($_POST['id'] ?? 0));
    if ($gruppe === null) {
        Helpers::flashSet('fehler', t('gruppen.nicht_gefunden'));
        Helpers::redirect('/gruppen.php');
    }

    if ($aktion === 'umbenennen') {
        $neuerName = trim((string) ($_POST['name'] ?? ''));
        if ($neuerName !== '' && mb_strlen($neuerName) <= 200) {
            $repo->umbenennen((int) $gruppe['id'], $neuerName);
            Protokoll::schreibe('gruppe_umbenannt', “„{$gruppe['name']}” → „{$neuerName}””);
            Helpers::flashSet('erfolg', t('gruppen.umbenannt'));
        }
    } elseif ($aktion === 'loeschen') {
        $repo->loeschen((int) $gruppe['id']);
        Protokoll::schreibe('gruppe_geloescht', “„{$gruppe['name']}””);
        Helpers::flashSet('erfolg', t('gruppen.geloescht'));
    }
    Helpers::redirect('/gruppen.php');
}

render('gruppen_liste', [
    'titel' => t('gruppen.titel'),
    'aktuelleSeite' => 'gruppen',
    'gruppen' => $repo->sichtbarFuer($benutzer),
    'istAdmin' => Auth::isAdmin($benutzer),
]);
