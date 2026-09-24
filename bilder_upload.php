<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\BildUpload;
use App\Helpers;
use App\Protokoll;

Auth::requireAdmin();
$ergebnis = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Überschreitet der Upload post_max_size, verwirft PHP alle Felder – auch das CSRF-Token.
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        Helpers::flashSet('fehler', 'Der Upload ist größer als vom Server erlaubt (' . ini_get('post_max_size') . '). Bitte in kleineren Teilen hochladen.');
        Helpers::redirect('/bilder_upload.php');
    }
    Helpers::checkCsrf();

    $upload = new BildUpload(!empty($_POST['ueberschreiben']));
    $upload->verarbeite($_FILES['dateien'] ?? []);
    if ($upload->gespeichert !== []) {
        Protokoll::schreibe('bilder_hochgeladen', count($upload->gespeichert) . ' Dateien');
    }
    $ergebnis = $upload;
}

render('bilder_upload', [
    'titel' => 'Bilder hochladen',
    'aktuelleSeite' => 'import',
    'ergebnis' => $ergebnis,
    'fehlend' => BildUpload::fehlendeBilder(),
    'limits' => [
        'datei' => (string) ini_get('upload_max_filesize'),
        'gesamt' => (string) ini_get('post_max_size'),
        'anzahl' => (int) ini_get('max_file_uploads'),
    ],
]);
