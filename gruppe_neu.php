<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;

$aktuellerBenutzer = Auth::requireAdmin();

render('gruppe_neu', [
    'titel' => t('gruppe_neu.titel'),
    'aktuelleSeite' => 'gruppen',
]);
