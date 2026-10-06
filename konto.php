<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BenutzerRepository;
use App\Helpers;
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
            Helpers::flashSet('erfolg', 'Deine Angaben wurden gespeichert.');
            Helpers::redirect('/konto.php');
        }
    } elseif ($aktion === 'passwort') {
        $neu = (string) ($_POST['passwort'] ?? '');
        if (!password_verify((string) ($_POST['aktuelles_passwort'] ?? ''), $benutzer['passwort_hash'])) {
            $fehler = 'Das aktuelle Passwort ist falsch.';
        } else {
            $fehler = Auth::passwortFehler($neu, (string) ($_POST['passwort_wiederholen'] ?? ''));
        }
        if ($fehler === null) {
            Auth::setzePasswort((int) $benutzer['id'], $neu);
            Protokoll::schreibe('passwort_geaendert');
            Helpers::flashSet('erfolg', 'Dein Passwort wurde geändert. Andere Geräte wurden abgemeldet.');
            Helpers::redirect('/konto.php');
        }
    }
}

render('konto', [
    'titel' => 'Mein Konto',
    'benutzer' => $benutzer,
    'fehler' => $fehler,
]);
