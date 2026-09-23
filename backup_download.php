<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;

Auth::requireAdmin();
$pdo = Database::get();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM backups WHERE id = :id');
$stmt->execute(['id' => $id]);
$backup = $stmt->fetch();

if ($backup === false) {
    http_response_code(404);
    exit('Backup wurde nicht gefunden.');
}

$pfad = BACKUPS_PATH . '/' . $backup['dateiname'];
if (!is_file($pfad)) {
    http_response_code(404);
    exit('Backup-Datei fehlt auf dem Server.');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $backup['dateiname'] . '"');
header('Content-Length: ' . filesize($pfad));
readfile($pfad);
