<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;
use Dompdf\Dompdf;
use Dompdf\Options;

$aktuellerBenutzer = Auth::requireLogin();
$istAdmin = Auth::isAdmin($aktuellerBenutzer);
$pdo = Database::get();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM gruppen WHERE id = :id');
$stmt->execute(['id' => $id]);
$gruppe = $stmt->fetch();

if ($gruppe === false) {
    http_response_code(404);
    exit('Gruppe wurde nicht gefunden.');
}

if (!$istAdmin && !in_array($id, Auth::sichtbareGruppenIds($aktuellerBenutzer), true)) {
    http_response_code(403);
    exit('Zugriff verweigert.');
}

$werke = $pdo->prepare(
    "SELECT k.*, (
        SELECT dateiname FROM bilder b WHERE b.kunstwerk_id = k.id
        ORDER BY ist_hauptbild DESC, sortierung ASC LIMIT 1
    ) AS bild_dateiname
    FROM kunstwerke k
    JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
    WHERE gk.gruppe_id = :id
    ORDER BY k.ort, k.maler, k.titel"
);
$werke->execute(['id' => $id]);
$werke = $werke->fetchAll();

function bild_als_data_uri(?string $dateiname): ?string
{
    if (!$dateiname) {
        return null;
    }
    $pfad = BILDER_PATH . '/' . $dateiname;
    if (!is_file($pfad)) {
        return null;
    }
    $typ = match (strtolower((string) pathinfo($pfad, PATHINFO_EXTENSION))) {
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
    return 'data:' . $typ . ';base64,' . base64_encode((string) file_get_contents($pfad));
}

ob_start();
?>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1C1B1A; font-size: 11pt; }
    .seite { page-break-after: always; padding-top: 20px; }
    .seite:last-child { page-break-after: auto; }
    .bild-rahmen { text-align: center; border: 6px solid #F7F7F5; padding: 10px; }
    .bild-rahmen img { max-width: 460px; max-height: 320px; }
    .etikett { text-align: center; margin-top: 16px; }
    .etikett .maler { font-variant: small-caps; letter-spacing: 1px; font-size: 12pt; }
    .etikett .titel { font-style: italic; font-size: 16pt; margin: 4px 0; }
    table.daten { width: 100%; margin-top: 24px; border-collapse: collapse; }
    table.daten td { padding: 4px 8px; border-bottom: 1px solid #E2E0DC; font-size: 10pt; }
    table.daten td.label { color: #6B6862; width: 160px; }
    h1 { font-size: 14pt; }
</style>
</head>
<body>
<?php foreach ($werke as $i => $w): ?>
<div class="seite">
    <h1><?= Helpers::e($gruppe['name']) ?></h1>
    <div class="bild-rahmen">
        <?php $uri = bild_als_data_uri($w['bild_dateiname']); ?>
        <?php if ($uri): ?>
            <img src="<?= $uri ?>">
        <?php else: ?>
            <p>Kein Bild hinterlegt</p>
        <?php endif; ?>
    </div>
    <div class="etikett">
        <div class="maler"><?= Helpers::e($w['maler']) ?></div>
        <div class="titel"><?= Helpers::e($w['titel']) ?></div>
    </div>
    <table class="daten">
        <tr><td class="label">Ort</td><td><?= Helpers::e($w['ort']) ?></td></tr>
        <tr><td class="label">Format</td><td><?= Helpers::e($w['format']) ?></td></tr>
        <tr><td class="label">Technik</td><td><?= Helpers::e($w['technik']) ?></td></tr>
        <tr><td class="label">Entstehungsjahr</td><td><?= Helpers::e((string) $w['entstehungsjahr']) ?></td></tr>
        <tr><td class="label">Ankaufjahr</td><td><?= Helpers::e((string) $w['ankaufjahr']) ?></td></tr>
        <tr><td class="label">Ankauf</td><td><?= Helpers::e($w['ankauf']) ?></td></tr>
        <tr><td class="label">Ankaufswert</td><td><?= Helpers::formatGeld($w['ankaufswert'] !== null ? (float) $w['ankaufswert'] : null) ?></td></tr>
        <tr><td class="label">Wert</td><td><?= Helpers::formatGeld($w['wert'] !== null ? (float) $w['wert'] : null) ?></td></tr>
    </table>
</div>
<?php endforeach; ?>
<?php if (!$werke): ?>
<div class="seite"><p>Diese Gruppe enthält keine Werke.</p></div>
<?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dateiname = preg_replace('/[^A-Za-z0-9_-]+/', '_', $gruppe['name']) . '_' . date('Y-m-d_Hi') . '.pdf';
$dompdf->stream($dateiname, ['Attachment' => true]);
