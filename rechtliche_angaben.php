<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Einstellungen;
use App\Helpers;
use App\Protokoll;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();

    $impressumUrl    = trim((string) ($_POST['impressum_url'] ?? ''));
    $impressumText   = trim((string) preg_replace('/\r\n?/', "\n", (string) ($_POST['impressum_text'] ?? '')));
    $datenschutzUrl  = trim((string) ($_POST['datenschutz_url'] ?? ''));
    $datenschutzText = trim((string) preg_replace('/\r\n?/', "\n", (string) ($_POST['datenschutz_text'] ?? '')));

    $fehler = null;
    if ($impressumUrl !== '' && !preg_match('~^https?://~i', $impressumUrl)) {
        $fehler = 'Impressum-URL muss mit http:// oder https:// beginnen.';
    } elseif ($datenschutzUrl !== '' && !preg_match('~^https?://~i', $datenschutzUrl)) {
        $fehler = 'Datenschutz-URL muss mit http:// oder https:// beginnen.';
    }

    if ($fehler !== null) {
        Helpers::flashSet('fehler', $fehler);
    } else {
        Einstellungen::setze('impressum_url', $impressumUrl);
        Einstellungen::setze('impressum_text', $impressumText);
        Einstellungen::setze('datenschutz_url', $datenschutzUrl);
        Einstellungen::setze('datenschutz_text', $datenschutzText);
        Protokoll::schreibe('rechtliche_angaben', 'Impressum/Datenschutz aktualisiert');
        Helpers::flashSet('erfolg', 'Rechtliche Angaben gespeichert.');
    }
    Helpers::redirect('/rechtliche_angaben.php');
}

render('rechtliche_angaben', [
    'titel'          => 'Rechtliche Angaben',
    'aktuelleSeite'  => 'rechtliche_angaben',
    'impressumUrl'   => Einstellungen::hol('impressum_url'),
    'impressumText'  => Einstellungen::hol('impressum_text'),
    'datenschutzUrl' => Einstellungen::hol('datenschutz_url'),
    'datenschutzText'=> Einstellungen::hol('datenschutz_text'),
]);
