<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireLogin();
$pdo = Database::get();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM kunstwerke WHERE id = :id');
$stmt->execute(['id' => $id]);
$werk = $stmt->fetch();

if ($werk === false) {
    http_response_code(404);
    exit('Werk wurde nicht gefunden.');
}

if (!Auth::isAdmin($aktuellerBenutzer)) {
    $sichtbar = Auth::sichtbareGruppenIds($aktuellerBenutzer);
    $check = $pdo->prepare(
        'SELECT COUNT(*) AS n FROM gruppe_kunstwerk WHERE kunstwerk_id = ? AND gruppe_id IN (' . (Helpers::platzhalter($sichtbar) ?: 'NULL') . ')'
    );
    $check->execute(array_merge([$id], $sichtbar));
    if ($sichtbar === [] || (int) $check->fetch()['n'] === 0) {
        http_response_code(403);
        exit('Zugriff verweigert: dieses Werk gehört zu keiner deiner zugewiesenen Gruppen.');
    }
}

$bilder = $pdo->prepare('SELECT * FROM bilder WHERE kunstwerk_id = :id ORDER BY ist_hauptbild DESC, sortierung ASC');
$bilder->execute(['id' => $id]);

render('werk_detail', [
    'titel' => $werk['titel'] ?: 'Werk',
    'aktuelleSeite' => 'werke',
    'werk' => $werk,
    'bilder' => $bilder->fetchAll(),
]);
