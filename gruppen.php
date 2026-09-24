<?php
declare(strict_types=1);

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
            Helpers::flashSet('fehler', 'Bitte einen Gruppennamen (höchstens 200 Zeichen) angeben.');
            Helpers::redirect('/gruppe_neu.php');
        }
        if ($werkIds === []) {
            Helpers::flashSet('fehler', 'Bitte mindestens ein Werk auswählen.');
            Helpers::redirect('/gruppe_neu.php');
        }

        $neueId = $repo->anlegen($name, (int) $benutzer['id'], $werkIds);
        $anzahl = count(\App\WerkRepository::neu()->mitgliedIds($neueId));
        Protokoll::schreibe('gruppe_angelegt', "„{$name}“ mit {$anzahl} Werken");
        Helpers::flashSet('erfolg', "Gruppe „{$name}“ wurde mit {$anzahl} Werken angelegt.");
        Helpers::redirect('/gruppe.php?id=' . $neueId . '&neu=1');
    }

    $gruppe = $repo->finde((int) ($_POST['id'] ?? 0));
    if ($gruppe === null) {
        Helpers::flashSet('fehler', 'Gruppe wurde nicht gefunden.');
        Helpers::redirect('/gruppen.php');
    }

    if ($aktion === 'umbenennen') {
        $neuerName = trim((string) ($_POST['name'] ?? ''));
        if ($neuerName !== '' && mb_strlen($neuerName) <= 200) {
            $repo->umbenennen((int) $gruppe['id'], $neuerName);
            Protokoll::schreibe('gruppe_umbenannt', "„{$gruppe['name']}“ → „{$neuerName}“");
            Helpers::flashSet('erfolg', 'Gruppenname wurde geändert.');
        }
    } elseif ($aktion === 'loeschen') {
        $repo->loeschen((int) $gruppe['id']);
        Protokoll::schreibe('gruppe_geloescht', "„{$gruppe['name']}“");
        Helpers::flashSet('erfolg', 'Gruppe wurde gelöscht (die enthaltenen Kunstwerke bleiben erhalten).');
    }
    Helpers::redirect('/gruppen.php');
}

render('gruppen_liste', [
    'titel' => 'Gruppen',
    'aktuelleSeite' => 'gruppen',
    'gruppen' => $repo->sichtbarFuer($benutzer),
    'istAdmin' => Auth::isAdmin($benutzer),
]);
