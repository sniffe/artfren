<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BenutzerRepository;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;

$aktuellerBenutzer = Auth::requireAdmin();
$repo = BenutzerRepository::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'anlegen') {
        $benutzername = trim((string) ($_POST['benutzername'] ?? ''));
        $echterName = trim((string) ($_POST['echter_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $passwort = (string) ($_POST['passwort'] ?? '');
        $rolle = ($_POST['rolle'] ?? '') === 'admin' ? 'admin' : 'eingeschraenkt';

        $fehler = BenutzerRepository::benutzernameFehler($benutzername)
            ?? BenutzerRepository::stammdatenFehler($echterName, $email)
            ?? Auth::passwortFehler($passwort, (string) ($_POST['passwort_wiederholen'] ?? ''))
            ?? ($repo->benutzernameVergeben($benutzername) ? t('benutzer.vergeben') : null);

        if ($fehler !== null) {
            Helpers::flashSet('fehler', $fehler);
        } else {
            $repo->anlegen($benutzername, $echterName, $email, $passwort, $rolle, Helpers::idListe($_POST['gruppen'] ?? []));
            Protokoll::schreibe('benutzer_angelegt', "„{$benutzername}“ ({$rolle})");
            Helpers::flashSet('erfolg', t('benutzer.angelegt'));
        }
        Helpers::redirect('/benutzer.php');
    }

    $ziel = $repo->finde((int) ($_POST['id'] ?? 0));
    if ($ziel === null) {
        Helpers::redirect('/benutzer.php');
    }

    if ($aktion === 'loeschen') {
        if ((int) $ziel['id'] === (int) $aktuellerBenutzer['id']) {
            Helpers::flashSet('fehler', t('benutzer.eigener_loeschen'));
        } else {
            $repo->loeschen((int) $ziel['id']);
            Protokoll::schreibe('benutzer_geloescht', "„{$ziel['benutzername']}“");
            Helpers::flashSet('erfolg', t('benutzer.geloescht'));
        }
    } elseif ($aktion === 'entsperren') {
        Auth::unlockUser((int) $ziel['id']);
        Protokoll::schreibe('benutzer_entsperrt', "„{$ziel['benutzername']}“");
        Helpers::flashSet('erfolg', t('benutzer.entsperrt'));
    }

    Helpers::redirect('/benutzer.php');
}

render('benutzer_liste', [
    'titel' => t('benutzer.titel'),
    'aktuelleSeite' => 'benutzer',
    'breit' => true,
    'benutzerListe' => $repo->alle(),
    'gruppen' => GruppeRepository::neu()->namen(),
    'zuordnungen' => $repo->alleZuordnungen(),
]);
