<?php
/**
 * Stile des Kachellayouts. Eigener Block, damit Einzelblatt und Liste
 * byte-gleich bleiben. Maße in mm, abgestimmt auf A4 mit 12 mm Rand:
 * 2x2 hoch (186 × 273 mm Inhalt), 3x2 quer (273 × 186 mm Inhalt).
 *
 * @var string $kachelRaster '2x2'|'3x2'
 * @var bool   $zeigeBild
 */
declare(strict_types=1);

$quer = $kachelRaster === '3x2';
// Kachelhöhe, Bildfläche und Schriftgrößen je Raster
// Summe je Seite mit Reserve: 2 × (Zelle + 5 mm Innenabstand) + 9 mm Abstände + 10 mm Kopf
// bleibt unter der Inhaltshöhe (273 mm hoch, 186 mm quer).
$kachelHoehe = $quer ? 74 : 116;   // mm, Zelle ohne Innenabstand
$bildHoehe   = $zeigeBild ? ($quer ? 44 : 72) : 0;
$textHoehe   = $kachelHoehe - $bildHoehe - ($zeigeBild ? 3 : 0);
$bildBreite  = $quer ? 82 : 84;    // mm, Platz für das Bild in der Zelle
$textBreite  = 80;                 // mm, nutzbare Textbreite in der Zelle (beide Raster)
?>
<style>
    @page { margin: 12mm; }
    .kachel-seite { page-break-after: always; }
    .kachel-seite.letzte { page-break-after: auto; }
    .kachel-kopf { font-size: 8pt; color: #6B6862; border-bottom: 1px solid #E2E0DC; padding-bottom: 4px; margin-bottom: 4mm; }
    table.kacheln { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 3mm 3mm; margin: -3mm; }
    table.kacheln td.kachel { height: <?= $kachelHoehe ?>mm; vertical-align: top; border: 1px solid #E2E0DC; padding: 2.5mm; }
    table.kacheln td.leer { border: none; }
    table.kachel-bild { width: 100%; border-collapse: collapse; }
    table.kachel-bild td { height: <?= $bildHoehe ?>mm; text-align: center; vertical-align: middle; padding: 0; }
    table.kachel-bild img { max-width: <?= $bildBreite ?>mm; max-height: <?= $bildHoehe ?>mm; }
    .kachel-kein-bild { color: #6B6862; font-size: 8pt; }
    .kachel-text { height: <?= $textHoehe ?>mm; overflow: hidden; margin-top: <?= $zeigeBild ? 3 : 0 ?>mm; }
    .kachel-titel { font-weight: bold; font-size: <?= $quer ? '9pt' : '10pt' ?>; line-height: 1.3; }
    .kachel-maler { font-size: <?= $quer ? '8pt' : '9pt' ?>; line-height: 1.3; margin-bottom: 1.5mm; }
    .kachel-feld { line-height: 1.3; }
    .kachel-feld .label { color: #6B6862; }
    .kachel-summe { font-size: 9pt; margin-top: 4mm; }
    .kachel-summe td { padding: 2px 8px 2px 0; }
</style>
