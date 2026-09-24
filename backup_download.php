<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Backup;
use App\Helpers;
use App\Protokoll;

Auth::requireAdmin();

$backup = Backup::finde((int) ($_GET['id'] ?? 0));
$pfad = $backup !== null ? Backup::pfad($backup) : null;
if ($pfad === null || !is_file($pfad)) {
    Helpers::abbrechen(404, 'Dieses Backup wurde nicht gefunden.');
}

Protokoll::schreibe('backup_heruntergeladen', $backup['dateiname']);
session_write_close();
@set_time_limit(0);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($pfad) . '"');
header('Content-Length: ' . filesize($pfad));
readfile($pfad);
