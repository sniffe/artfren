<?php
declare(strict_types=1);

// Bild für ein Werk hochladen oder aus dem Bilder-Ordner zuweisen
// (Dialog über den Bild-Platzhalter in der Werkliste).

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BildUpload;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('/werke.php');
}
$zurueck = Helpers::ruecksprung($_POST['zurueck'] ?? null, '/werke.php');

// Überschreitet der Upload post_max_size, verwirft PHP alle Felder – auch das CSRF-Token.
if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    Helpers::flashSet('fehler', 'Das Bild ist größer als vom Server erlaubt (' . ini_get('post_max_size') . ').');
    Helpers::redirect('/werke.php');
}
Helpers::checkCsrf();

$repo = WerkRepository::neu();
$werk = $repo->finde((int) ($_POST['werk_id'] ?? 0));
if ($werk === null) {
    Helpers::abbrechen(404, 'Dieses Werk wurde nicht gefunden.');
}

[$name, $fehler] = BildUpload::zuweisenAusFormular($repo, (int) $werk['id'], $_FILES['bild'] ?? null, (string) ($_POST['vorhandenes_bild'] ?? ''));

if ($fehler !== null) {
    Helpers::flashSet('fehler', $fehler);
} elseif ($name === null) {
    Helpers::flashSet('hinweis', 'Es wurde kein Bild ausgewählt.');
} else {
    Protokoll::schreibe('werk_bild', "{$werk['maler']} – {$werk['titel']}: {$name}");
    Helpers::flashSet('erfolg', "Bild „{$name}“ wurde „{$werk['titel']}“ zugewiesen.");
}
Helpers::redirect($zurueck);
