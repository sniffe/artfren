<?php
/** @var array $werkOhneBild */
/** @var int $anzahlOhneBild */
/** @var array $werkOhneBeschr */
/** @var int $anzahlOhneBeschr */
/** @var array $werkUnvollstaendig */
/** @var int $anzahlUnvollstaendig */
/** @var array $duplikate */
declare(strict_types=1);

use App\Helpers;

$zeigeWerkliste = static function (array $werke, int $gesamt, int $max = 50): void {
    if (!$werke) {
        echo '<p class="text-sekundaer text-klein">Keine Einträge gefunden.</p>';
        return;
    }
    echo '<ul class="text-klein">';
    foreach ($werke as $w) {
        $text = trim(($w['ort'] ?? '') . ' – ' . ($w['maler'] ?? '') . ' – ' . ($w['titel'] ?? ''), ' –');
        echo '<li><a href="/werk_bearbeiten.php?id=' . (int) $w['id'] . '">' . Helpers::e($text) . '</a></li>';
    }
    if ($gesamt > $max) {
        echo '<li class="text-sekundaer">… und ' . ($gesamt - $max) . ' weitere</li>';
    }
    echo '</ul>';
};
?>
<div class="toolbar">
    <h2 style="margin:0;">Datenprüfung</h2>
</div>
<p class="text-sekundaer">Übersicht über Qualitätsprobleme im Bestand. Klick auf einen Eintrag öffnet das Bearbeiten-Formular.</p>

<div class="kennzahlen mt-m">
    <div class="karte"><strong><?= $anzahlOhneBild ?></strong><span class="text-klein text-sekundaer">ohne Bild</span></div>
    <div class="karte"><strong><?= $anzahlOhneBeschr ?></strong><span class="text-klein text-sekundaer">ohne öffentl. Beschreibung</span></div>
    <div class="karte"><strong><?= $anzahlUnvollstaendig ?></strong><span class="text-klein text-sekundaer">ohne Ort oder Maler</span></div>
    <div class="karte"><strong><?= count($duplikate) ?></strong><span class="text-klein text-sekundaer">doppelte Kombinationen</span></div>
</div>

<div class="feldreihe mt-l" style="align-items:flex-start;">
    <div class="karte" style="flex:1;min-width:280px;">
        <h3>Ohne Bild (<?= $anzahlOhneBild ?>)</h3>
        <p class="text-klein text-sekundaer">Diese Werke haben weder ein hochgeladenes noch ein verknüpftes Bild.</p>
        <?php $zeigeWerkliste($werkOhneBild, $anzahlOhneBild); ?>
        <?php if ($anzahlOhneBild > 0): ?>
            <p class="mt-m"><a href="/werke.php?ohne_bild=1" class="btn btn--klein">In Werkliste anzeigen</a></p>
        <?php endif; ?>
    </div>
    <div class="karte" style="flex:1;min-width:280px;">
        <h3>Ohne öffentliche Beschreibung (<?= $anzahlOhneBeschr ?>)</h3>
        <p class="text-klein text-sekundaer">Das Feld „Beschreibung öffentlich" ist leer.</p>
        <?php $zeigeWerkliste($werkOhneBeschr, $anzahlOhneBeschr); ?>
    </div>
</div>

<?php if ($werkUnvollstaendig): ?>
<div class="karte mt-m">
    <h3>Unvollständige Stammdaten (<?= $anzahlUnvollstaendig ?>)</h3>
    <p class="text-klein text-sekundaer">Ort oder Maler fehlt – Import und Abgleich funktionieren für diese Werke möglicherweise nicht korrekt.</p>
    <?php $zeigeWerkliste($werkUnvollstaendig, $anzahlUnvollstaendig); ?>
</div>
<?php endif; ?>

<?php if ($duplikate): ?>
<div class="karte mt-m">
    <h3>Doppelte Ort/Maler/Titel-Kombinationen (<?= count($duplikate) ?>)</h3>
    <p class="text-klein text-sekundaer">Mehrere Werke teilen dieselbe Kombination. Beim Import werden sie der Reihe nach zugeordnet – eindeutige Titel (z. B. „o.T. 1") verhindern Verwechslungen.</p>
    <table class="liste">
        <thead>
            <tr>
                <th>Ort</th>
                <th>Maler</th>
                <th>Titel</th>
                <th class="num">Anzahl</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($duplikate as $d): ?>
            <tr>
                <td class="text-klein"><?= Helpers::e($d['ort'] ?? '') ?></td>
                <td class="text-klein"><?= Helpers::e($d['maler'] ?? '') ?></td>
                <td class="text-klein"><?= Helpers::e($d['titel'] ?? '') ?></td>
                <td class="num text-klein"><?= (int) $d['anzahl'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
