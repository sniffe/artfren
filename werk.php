<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;
use App\WerkRepository;

$benutzer = Auth::requireLogin();
$id = (int) ($_GET['id'] ?? 0);

$werk = WerkRepository::neu()->finde($id);
if ($werk === null || !Auth::darfWerkSehen($benutzer, $id)) {
    // Gleiche Antwort für "gibt es nicht" und "darfst du nicht" – verrät keine IDs.
    Helpers::abbrechen(404, 'Dieses Werk wurde nicht gefunden.');
}

$bilder = Database::get()->prepare('SELECT * FROM bilder WHERE kunstwerk_id = :id ORDER BY ist_hauptbild DESC, sortierung, id');
$bilder->execute(['id' => $id]);

render('werk_detail', [
    'titel' => $werk['titel'] ?: 'Werk',
    'aktuelleSeite' => Auth::isAdmin($benutzer) ? 'werke' : 'gruppen',
    'werk' => $werk,
    'bilder' => $bilder->fetchAll(),
]);
