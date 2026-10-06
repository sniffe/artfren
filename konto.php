<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BenutzerRepository;
use App\Helpers;
use App\I18n;
use App\Protokoll;

$benutzer = Auth::requireLogin();
$repo = BenutzerRepository::neu();
$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'kontakt') {
        $echterName = trim((string) ($_POST['echter_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $fehler = BenutzerRepository::stammdatenFehler($echterName, $email);
        if ($fehler === null) {
            $repo->aktualisiereKontakt((int) $benutzer['id'], $echterName, $email);
            Helpers::flashSet('erfolg', t('konto.gespeichert'));
            Helpers::redirect('/konto.php');
        }
    } elseif ($aktion === 'passwort') {
        $neu = (string) ($_POST['passwort'] ?? '');
        if (!password_verify((string) ($_POST['aktuelles_passwort'] ?? ''), $benutzer['passwort_hash'])) {
            $fehler = t('konto.aktuelles_falsch');
        } else {
            $fehler = Auth::passwortFehler($neu, (string) ($_POST['passwort_wiederholen'] ?? ''));
        }
        if ($fehler === null) {
            Auth::setzePasswort((int) $benutzer['id'], $neu);
            Protokoll::schreibe('passwort_geaendert');
            Helpers::flashSet('erfolg', t('konto.passwort_geaendert'));
            Helpers::redirect('/konto.php');
        }
    } elseif ($aktion === 'sprache') {
        $sprache = (string) ($_POST['sprache'] ?? '');
        if (isset(I18n::SPRACHEN[$sprache])) {
            $repo->aktualisiereSprache((int) $benutzer['id'], $sprache);
            $_SESSION['sprache'] = $sprache;
            I18n::setze($sprache);
        }
        Helpers::flashSet('erfolg', t('konto.sprache_gespeichert'));
        Helpers::redirect('/konto.php');
    }
}

// Benutzer neu laden damit 'sprache'-Spalte vorhanden ist (nach Migration 009)
$benutzer = $repo->finde((int) $benutzer['id']) ?? $benutzer;

render('konto', [
    'titel' => t('konto.titel'),
    'benutzer' => $benutzer,
    'fehler' => $fehler,
]);
