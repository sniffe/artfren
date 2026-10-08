<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BenutzerRepository;
use App\GruppeRepository;
use App\Helpers;
use App\I18n;
use App\Protokoll;

$aktuellerBenutzer = Auth::requireAdmin();
$repo = BenutzerRepository::neu();

$ziel = $repo->finde((int) ($_GET['id'] ?? $_POST['id'] ?? 0));
if ($ziel === null) {
    Helpers::abbrechen(404, t('fehler.nicht_gefunden'));
}
$zielId = (int) $ziel['id'];
$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'speichern') {
        $echterName = trim((string) ($_POST['echter_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rolle = ($_POST['rolle'] ?? '') === 'admin' ? 'admin' : 'eingeschraenkt';

        $fehler = BenutzerRepository::stammdatenFehler($echterName, $email);
        if ($fehler === null && $ziel['rolle'] === 'admin' && $rolle !== 'admin' && $repo->anzahlAdmins() <= 1) {
            $fehler = t('benutzer_bearbeiten.letzter_admin');
        }

        if ($fehler === null) {
            $repo->aktualisieren($zielId, $echterName, $email, $rolle, Helpers::idListe($_POST['gruppen'] ?? []));
            $sprache = (string) ($_POST['sprache'] ?? '');
            $repo->aktualisiereSprache($zielId, isset(I18n::SPRACHEN[$sprache]) ? $sprache : null);
            $darfExcel = isset($_POST['darf_excel']) ? 1 : 0;
            $darfBilder = isset($_POST['darf_bilder_export']) ? 1 : 0;
            $repo->aktualisiereExportRechte($zielId, $darfExcel, $darfBilder);
            $details = "„{$ziel['benutzername']}“" . ($rolle !== $ziel['rolle'] ? ", Rolle → {$rolle}" : '');
            Protokoll::schreibe('benutzer_geaendert', $details);
            Helpers::flashSet('erfolg', t('benutzer_bearbeiten.gespeichert'));
            // Hat sich der Admin selbst herabgestuft, gibt es die Verwaltung für ihn nicht mehr.
            Helpers::redirect($zielId === (int) $aktuellerBenutzer['id'] && $rolle !== 'admin' ? '/gruppen.php' : '/benutzer.php');
        }
    } elseif ($aktion === 'passwort') {
        $passwort = (string) ($_POST['passwort'] ?? '');
        $fehler = Auth::passwortFehler($passwort, (string) ($_POST['passwort_wiederholen'] ?? ''));
        if ($fehler === null) {
            Auth::setzePasswort($zielId, $passwort);
            Protokoll::schreibe('passwort_zurueckgesetzt', "„{$ziel['benutzername']}“");
            Helpers::flashSet('erfolg', t('benutzer_bearbeiten.passwort_gesetzt', ['name' => $ziel['benutzername']]));
            Helpers::redirect('/benutzer.php');
        }
    }
    // Bei Fehlern: Eingaben im Formular stehen lassen.
    foreach (['echter_name', 'email', 'rolle'] as $feld) {
        if (isset($_POST[$feld]) && is_string($_POST[$feld])) {
            $ziel[$feld] = trim($_POST[$feld]);
        }
    }
}

render('benutzer_bearbeiten', [
    'titel' => t('benutzer_bearbeiten.titel', ['name' => $ziel['benutzername']]),
    'aktuelleSeite' => 'benutzer',
    'ziel' => $ziel,
    'fehler' => $fehler,
    'gruppen' => GruppeRepository::neu()->namen(),
    'ausgewaehlt' => isset($_POST['gruppen']) ? Helpers::idListe($_POST['gruppen']) : $repo->gruppenIds($zielId),
]);
