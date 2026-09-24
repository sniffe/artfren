<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;

$benutzer = Auth::currentUser();
Helpers::redirect($benutzer !== null ? Auth::startseite($benutzer) : '/login.php');
