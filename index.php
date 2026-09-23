<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Helpers;

Helpers::redirect(Auth::currentUser() !== null ? '/werke.php' : '/login.php');
