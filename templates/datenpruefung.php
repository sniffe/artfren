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
        echo '<p class="text-sekundaer text-klein">' . Helpers::e(t('datenpruef.keine_eintraege')) . '</p>';
        return;
    }
    echo '<ul class="text-klein">';
    foreach ($werke as $w) {
        $text = trim(($w['ort'] ?? '') . ' – ' . ($w['maler'] ?? '') . ' – ' . ($w['titel'] ?? ''), ' –');
        echo '<li><a href="/werk_bearbeiten.php?id=' . (int) $w['id'] . '">' . Helpers::e($text) . '</a></li>';
    }
    if ($gesamt > $max) {
        echo '<li class="text-sekundaer">' . Helpers::e(t('datenpruef.und_weitere', ['n' => $gesamt - $max])) . '</li>';
    }
    echo '</ul>';
};
?>
<div class="toolbar">
    <h2 style="margin:0;"><?= Helpers::e(t('datenpruef.titel')) ?></h2>
</div>
<p class="text-sekundaer"><?= Helpers::e(t('datenpruef.beschreibung')) ?></p>

<div class="kennzahlen mt-m">
    <div class="karte"><strong><?= $anzahlOhneBild ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.ohne_bild')) ?></span></div>
    <div class="karte"><strong><?= $anzahlOhneBeschr ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.ohne_beschr')) ?></span></div>
    <div class="karte"><strong><?= $anzahlUnvollstaendig ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.unvollstaendig')) ?></span></div>
    <div class="karte"><strong><?= count($duplikate) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.duplikate')) ?></span></div>
</div>

<div class="feldreihe mt-l" style="align-items:flex-start;">
    <div class="karte" style="flex:1;min-width:280px;">
        <h3><?= Helpers::e(t('datenpruef.ohne_bild_titel', ['n' => $anzahlOhneBild])) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.ohne_bild_hinweis')) ?></p>
        <?php $zeigeWerkliste($werkOhneBild, $anzahlOhneBild); ?>
        <?php if ($anzahlOhneBild > 0): ?>
            <p class="mt-m"><a href="/werke.php?ohne_bild=1" class="btn btn--klein"><?= Helpers::e(t('datenpruef.in_werkliste')) ?></a></p>
        <?php endif; ?>
    </div>
    <div class="karte" style="flex:1;min-width:280px;">
        <h3><?= Helpers::e(t('datenpruef.ohne_beschr_titel', ['n' => $anzahlOhneBeschr])) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.ohne_beschr_hinweis')) ?></p>
        <?php $zeigeWerkliste($werkOhneBeschr, $anzahlOhneBeschr); ?>
    </div>
</div>

<?php if ($werkUnvollstaendig): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('datenpruef.unvollst_titel', ['n' => $anzahlUnvollstaendig])) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.unvollst_hinweis')) ?></p>
    <?php $zeigeWerkliste($werkUnvollstaendig, $anzahlUnvollstaendig); ?>
</div>
<?php endif; ?>

<?php if ($duplikate): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('datenpruef.duplikate_titel', ['n' => count($duplikate)])) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('datenpruef.duplikate_hinweis')) ?></p>
    <table class="liste">
        <thead>
            <tr>
                <th><?= Helpers::e(t('feld.ort')) ?></th>
                <th><?= Helpers::e(t('feld.maler')) ?></th>
                <th><?= Helpers::e(t('feld.titel')) ?></th>
                <th class="num"><?= Helpers::e(t('datenpruef.anzahl')) ?></th>
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
