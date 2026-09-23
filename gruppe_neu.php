<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;

$aktuellerBenutzer = Auth::requireAdmin();

render('gruppe_neu', [
    'titel' => 'Neue Gruppe anlegen',
    'aktuelleSeite' => 'gruppen',
]);
