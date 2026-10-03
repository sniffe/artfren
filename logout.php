<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;

// Nur per POST mit CSRF-Token: sonst könnte eine fremde Seite per <img src="/logout.php"> abmelden.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('/');
}
Helpers::checkCsrf();

Auth::logout();
Helpers::redirect('/login.php');
