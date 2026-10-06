<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\ExportProfile;
use App\Helpers;
use App\Protokoll;

Auth::requireAdmin();
$pdo = Database::get();

$aktion = (string) ($_POST['aktion'] ?? '');

// ──────────────────────────────────────────── POST-Aktionen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();

    if ($aktion === 'speichern') {
        $id = ($_POST['id'] ?? '') !== '' ? (int) $_POST['id'] : null;
        $fehler = ExportProfile::fehler($_POST, $pdo, $id);
        if ($fehler) {
            Helpers::flashSet('fehler', $fehler);
            Helpers::redirect('/export_profile.php?' . ($id ? "aktion=bearbeiten&id={$id}" : 'aktion=neu'));
        }
        $neueId = ExportProfile::speichern($pdo, $_POST + ['erstellt_von' => Auth::benutzer()['id']], $id);
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
        if ($id === null) {
            Protokoll::schreibe('exportprofil_angelegt', "„{$name}"");
            Helpers::flashSet('erfolg', t('profil.angelegt'));
        } else {
            Protokoll::schreibe('exportprofil_geaendert', "„{$name}"");
            Helpers::flashSet('erfolg', t('profil.geaendert_flash'));
        }
        Helpers::redirect('/export_profile.php');
    }

    if ($aktion === 'loeschen') {
        $id = (int) ($_POST['id'] ?? 0);
        $profil = ExportProfile::finde($pdo, $id);
        if ($profil) {
            ExportProfile::loeschen($pdo, $id);
            Protokoll::schreibe('exportprofil_geloescht', "„{$profil['name']}"");
            Helpers::flashSet('erfolg', t('profil.geloescht'));
        }
        Helpers::redirect('/export_profile.php');
    }

    if ($aktion === 'duplizieren') {
        $id = (int) ($_POST['id'] ?? 0);
        $profil = ExportProfile::finde($pdo, $id);
        if ($profil) {
            $benutzer = Auth::benutzer();
            $neueId = ExportProfile::duplizieren($pdo, $id, (int) $benutzer['id']);
            Protokoll::schreibe('exportprofil_dupliziert', "„{$profil['name']}"");
            Helpers::flashSet('erfolg', t('profil.dupliziert'));
            Helpers::redirect('/export_profile.php?aktion=bearbeiten&id=' . $neueId);
        }
        Helpers::redirect('/export_profile.php');
    }

    Helpers::redirect('/export_profile.php');
}

// ──────────────────────────────────────────── GET-Ansichten
$getAktion = (string) ($_GET['aktion'] ?? '');
$id = ($_GET['id'] ?? '') !== '' ? (int) $_GET['id'] : null;
$aktuellesProfil = $id !== null ? ExportProfile::finde($pdo, $id) : null;

render('export_profile', [
    'titel'         => t('profil.titel'),
    'aktuelleSeite' => 'export',
    'aktiverReiter' => 'profile',
    'getAktion'     => $getAktion,
    'profile'       => ExportProfile::alle($pdo),
    'profil'        => $aktuellesProfil,
    'standardFelder'=> ExportProfile::standardFelder(),
]);
