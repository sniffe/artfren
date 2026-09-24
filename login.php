<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;

$benutzer = Auth::currentUser();
if ($benutzer !== null) {
    Helpers::redirect(Auth::startseite($benutzer));
}

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $benutzername = trim((string) ($_POST['benutzername'] ?? ''));
    $passwort = (string) ($_POST['passwort'] ?? '');

    $ergebnis = Auth::login($benutzername, $passwort);
    if ($ergebnis['ok']) {
        Helpers::redirect(Auth::startseite(Auth::requireLogin()));
    }
    $fehler = $ergebnis['fehler'];
}

render('login', ['titel' => 'Anmelden', 'fehler' => $fehler]);
