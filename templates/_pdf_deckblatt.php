<?php
/**
 * Deckblatt vor dem Inhalt (alle Layouts): Logo, Gruppenname, Erstellungsdatum,
 * Anzahl der Werke. Das Papierformat folgt dem Layout (eine Seite im selben Format).
 *
 * @var array $gruppe
 * @var array $werke
 * @var bool  $querformat
 */
declare(strict_types=1);

use App\Einstellungen;
use App\Helpers;
use App\I18n;

// Logo als data:-URI; dompdf lädt keine externen Dateien (isRemoteEnabled = false).
$logoUri = null;
$logoDatei = Einstellungen::logoPfad();
if ($logoDatei !== null && is_file(DATA_PATH . '/logo/' . $logoDatei)) {
    $logoPfad = DATA_PATH . '/logo/' . $logoDatei;
    $endung = strtolower(pathinfo($logoDatei, PATHINFO_EXTENSION));
    if ($endung === 'webp' && function_exists('imagecreatefromwebp')) {
        // dompdf kann WebP nicht zuverlässig darstellen: als PNG einbetten.
        $bild = @imagecreatefromwebp($logoPfad);
        if ($bild !== false) {
            imagesavealpha($bild, true);
            ob_start();
            imagepng($bild);
            $logoUri = 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
            imagedestroy($bild);
        }
    } else {
        $mime = match ($endung) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => null,
        };
        if ($mime !== null) {
            $logoUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPfad));
        }
    }
}
?>
<div style="page-break-after: always; text-align: center; padding-top: <?= $querformat ? '35mm' : '70mm' ?>;">
    <?php if ($logoUri !== null): ?>
    <div style="margin-bottom: 14mm;"><img src="<?= $logoUri ?>" style="max-width: 80mm; max-height: 30mm;"></div>
    <?php endif; ?>
    <div style="font-size: 24pt; line-height: 1.25;"><?= Helpers::e($gruppe['name']) ?></div>
    <div style="font-size: 11pt; color: #6B6862; margin-top: 8mm;"><?= Helpers::e(t('pdf.erstellt_am', ['datum' => date('d.m.Y')])) ?></div>
    <div style="font-size: 11pt; color: #6B6862; margin-top: 2mm;"><?= Helpers::e(I18n::plural(count($werke), 'pdf.liste_werke', ['n' => count($werke)])) ?></div>
</div>
