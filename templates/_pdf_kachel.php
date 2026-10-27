<?php
/**
 * Kachellayout: 2x2 (A4 hoch) oder 3x2 (A4 quer) Werke je Seite.
 * Jede Seite ist eine eigene Tabelle mit fester Zeilenhöhe; Bild und Text haben
 * feste Flächen, damit keine Kachel über die Seite hinauswächst oder geteilt wird
 * (dompdf kann Flexbox/Grid nur teilweise und keine Zeilen begrenzen).
 *
 * @var array  $gruppe
 * @var array  $werke   jeweils mit 'bild_data_uri'
 * @var array  $pdfFelder
 * @var bool   $zeigeBild
 * @var bool   $zeigeTitelblock
 * @var bool   $leerAusblenden
 * @var bool   $zeigeSumme
 * @var string $kachelRaster '2x2'|'3x2'
 * @var float  $textHoehe, $textBreite  Maße der Textfläche in mm (aus _pdf_kachel_stil.php)
 */
declare(strict_types=1);

use App\Helpers;
use App\I18n;

$quer      = $kachelRaster === '3x2';
$spalten   = $quer ? 3 : 2;
$jeSeite   = $quer ? 6 : 4;
$maxText   = $quer ? 60 : 80;    // Zeichen für normale Felder
$maxLang   = $quer ? 110 : 160;  // Zeichen für lange Textfelder
$geldFelder = array_values(array_filter($pdfFelder, static fn(array $f): bool => $f['typ'] === 'geld'));
// Titel und Künstler stehen im Titelblock; als Feldzeile nur, wenn der Titelblock aus ist.
$zeilenFelder = array_values(array_filter($pdfFelder, static fn(array $f): bool =>
    !($zeigeTitelblock && in_array($f['key'], ['titel', 'maler'], true))));

$summen     = array_fill_keys(array_column($geldFelder, 'key'), 0.0);
$ohneAngabe = array_fill_keys(array_column($geldFelder, 'key'), 0);
foreach ($werke as $w) {
    foreach ($geldFelder as $f) {
        $v = $w[$f['key']] ?? null;
        if ($v === null || $v === '') {
            $ohneAngabe[$f['key']]++;
        } else {
            $summen[$f['key']] += (float) $v;
        }
    }
}

/** Feldzeilen einer Kachel: [label, wert (escaped), klartext für die Breitenschätzung]. */
$feldZeilen = static function (array $w) use ($zeilenFelder, $leerAusblenden, $maxText, $maxLang): array {
    $zeilen = [];
    foreach ($zeilenFelder as $f) {
        $wert = $w[$f['key']] ?? null;
        $leer = $wert === null || $wert === '';
        if ($leer && $leerAusblenden) {
            continue;
        }
        if ($leer) {
            $klar = '–';
        } elseif ($f['typ'] === 'geld') {
            $klar = html_entity_decode(strip_tags(Helpers::formatGeld((float) $wert)), ENT_QUOTES, 'UTF-8');
        } else {
            $text = preg_replace('/\s+/u', ' ', trim((string) $wert)) ?? '';
            $klar = mb_strimwidth($text, 0, $f['typ'] === 'langtext' ? $maxLang : $maxText, '…');
        }
        if ($leerAusblenden && $klar === '') {
            continue;
        }
        $anzeige = ($f['typ'] === 'geld' && !$leer) ? Helpers::formatGeld((float) $wert) : Helpers::e($klar);
        $zeilen[] = [t($f['label']), $anzeige, t($f['label']) . ': ' . $klar];
    }
    return $zeilen;
};

/*
 * Wie viel passt in die Textfläche? dompdf schneidet mit overflow:hidden zwar ab,
 * rechnet den verborgenen Text aber beim Seitenumbruch mit. Deshalb wird vorab
 * geschätzt (DejaVu Sans, mittlere Zeichenbreite 0,58 em, Zeilenhöhe 1,35) und nur
 * so viel ausgegeben, wie sicher passt; der Rest wird als „+ n weitere“ genannt.
 */
$mm = static fn(float $pt): float => $pt * 0.3528;
$zeichenJeZeile = static fn(float $pt): int => max(10, (int) floor($textBreite / ($mm($pt) * 0.58)));
$zeilenFuer = static fn(string $text, float $pt): int => max(1, (int) ceil(mb_strwidth($text) / $zeichenJeZeile($pt)));
$titelPt = $quer ? 9.0 : 10.0;
$malerPt = $quer ? 8.0 : 9.0;
$groessen = $quer ? [7.0, 6.5, 6.0] : [8.0, 7.0, 6.5];
// Titel fett: höchstens zwei Zeilen; Künstler: eine Zeile.
$titelZeichen = 2 * $zeichenJeZeile($titelPt * 1.15) - 1;
$malerZeichen = $zeichenJeZeile($malerPt) - 1;
$kurzTitel = static fn(array $w): string => mb_strimwidth((string) ($w['titel'] ?? ''), 0, $titelZeichen, '…');
$kurzMaler = static fn(array $w): string => mb_strimwidth((string) ($w['maler'] ?? ''), 0, $malerZeichen, '…');
$planeKachel = static function (array $w, array $zeilen) use (
    $zeigeTitelblock, $textHoehe, $titelPt, $malerPt, $groessen, $mm, $zeilenFuer, $kurzTitel
): array {
    $platz = $textHoehe * 0.80; // Reserve für Rundung und Schriftmetrik
    if ($zeigeTitelblock) {
        $platz -= min(2, $zeilenFuer($kurzTitel($w), $titelPt * 1.15)) * $mm($titelPt) * 1.35;
        $platz -= $mm($malerPt) * 1.35 + 1.5;
    }
    foreach ($groessen as $pt) {
        $hoehe = 0.0;
        foreach ($zeilen as $z) {
            $hoehe += $zeilenFuer($z[2], $pt) * $mm($pt) * 1.35;
        }
        if ($hoehe <= $platz) {
            return [$pt, $zeilen, 0];
        }
    }
    // Auch in der kleinsten Schrift zu viel: Zeilen kürzen, eine Zeile für den Hinweis freihalten.
    $pt = end($groessen);
    $hoehe = $mm($pt) * 1.35;
    $behalten = [];
    foreach ($zeilen as $z) {
        $h = $zeilenFuer($z[2], $pt) * $mm($pt) * 1.35;
        if ($hoehe + $h > $platz) {
            break;
        }
        $hoehe += $h;
        $behalten[] = $z;
    }
    return [$pt, $behalten, count($zeilen) - count($behalten)];
};

$seiten = array_chunk($werke, $jeSeite);
$anzahl = count($werke);
foreach ($seiten as $s => $seitenWerke):
    $erste = $s * $jeSeite + 1;
    $letzte = $erste + count($seitenWerke) - 1;
    $istLetzte = $s === count($seiten) - 1;
?>
<div class="kachel-seite<?= $istLetzte ? ' letzte' : '' ?>">
    <div class="kachel-kopf"><?= Helpers::e($gruppe['name']) ?> · <?= Helpers::e($erste === $letzte
        ? t('pdf.werk_n_von_m', ['n' => $erste, 'm' => $anzahl])
        : t('pdf.werke_von_bis', ['a' => $erste, 'b' => $letzte, 'm' => $anzahl])) ?></div>
    <table class="kacheln">
        <?php foreach (array_chunk($seitenWerke, $spalten) as $reihe): ?>
        <tr>
            <?php foreach ($reihe as $w): ?>
            <?php [$schrift, $zeilen, $weitere] = $planeKachel($w, $feldZeilen($w)); ?>
            <td class="kachel">
                <?php if ($zeigeBild): ?>
                <table class="kachel-bild"><tr><td>
                    <?php if ($w['bild_data_uri']): ?>
                        <img src="<?= $w['bild_data_uri'] ?>">
                    <?php else: ?>
                        <span class="kachel-kein-bild"><?= Helpers::e(t('pdf.kein_bild')) ?></span>
                    <?php endif; ?>
                </td></tr></table>
                <?php endif; ?>
                <div class="kachel-text" style="font-size:<?= $schrift ?>pt;">
                    <?php if ($zeigeTitelblock): ?>
                    <div class="kachel-titel"><?= Helpers::e($kurzTitel($w)) ?></div>
                    <div class="kachel-maler"><?= Helpers::e($kurzMaler($w)) ?></div>
                    <?php endif; ?>
                    <?php foreach ($zeilen as [$label, $anzeige]): ?>
                    <div class="kachel-feld"><span class="label"><?= Helpers::e($label) ?>:</span> <?= $anzeige ?></div>
                    <?php endforeach; ?>
                    <?php if ($weitere > 0): ?>
                    <div class="kachel-feld label"><?= Helpers::e(I18n::plural($weitere, 'pdf.weitere_angaben')) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <?php endforeach; ?>
            <?php for ($leer = count($reihe); $leer < $spalten; $leer++): ?>
            <td class="leer"></td>
            <?php endfor; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($istLetzte && $zeigeSumme && $geldFelder): ?>
    <table class="kachel-summe">
        <?php foreach ($geldFelder as $f): ?>
        <tr>
            <td><?= Helpers::e(t('pdf.summe_label')) ?> <?= Helpers::e(t($f['label'])) ?>:</td>
            <td><strong><?= Helpers::formatGeld($summen[$f['key']]) ?></strong>
                <?php if ($ohneAngabe[$f['key']] > 0): ?> <?= Helpers::e(t('pdf.ohne_angabe', ['m' => $ohneAngabe[$f['key']]])) ?><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php endforeach; ?>
