<?php
/** @var array $abgleich */
/** @var string $tmp_datei */
/** @var array<int,string> $mapping */
/** @var int $gesamtzeilen */
/** @var int $uebersprungen */
/** @var array $ungueltigeDateinamen */
/** @var int $aliasErsetzt */
/** @var string[] $nichtZugeordnet */
declare(strict_types=1);

use App\Helpers;
use App\TabellenImport;

$zeigeListe = static function (array $eintraege, int $max = 15, bool $mitUnterschieden = false): void {
    foreach (array_slice($eintraege, 0, $max) as $eintrag) {
        $d = $eintrag['daten'];
        $text = trim(($d['ort'] ?? '') . ' – ' . ($d['maler'] ?? '') . ' – ' . ($d['titel'] ?? ''), ' –');
        echo '<li>' . Helpers::e(t('import.zeile_prefix')) . ' ' . (int) $eintrag['zeilennummer'] . ': ' . Helpers::e($text);
        if ($mitUnterschieden && !empty($eintrag['unterschiede'])) {
            $felder = array_map(static fn($f) => TabellenImport::zielfelder()[$f] ?? $f, $eintrag['unterschiede']);
            echo ' <span class="text-sekundaer">(' . Helpers::e(implode(', ', $felder)) . ')</span>';
        }
        if (isset($d['bild_dateiname']) && $d['bild_dateiname'] !== null && !$mitUnterschieden) {
            echo ' <span class="text-sekundaer">– ' . Helpers::e($d['bild_dateiname']) . '</span>';
        }
        echo '</li>';
    }
    if (count($eintraege) > $max) {
        echo '<li class="text-sekundaer">' . Helpers::e(t('import.und_weitere', ['n' => count($eintraege) - $max])) . '</li>';
    }
};

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<h2><?= Helpers::e(t('import.abgleich_titel')) ?></h2>
<p class="text-sekundaer">
    <?= Helpers::e($uebersprungen
        ? t('import.abgleich_uebersprungen', ['n' => $gesamtzeilen, 'ue' => $uebersprungen])
        : t('import.abgleich_werke', ['n' => $gesamtzeilen])
    ) ?>
    <?php if ($aliasErsetzt): ?>
        <?= Helpers::e(t('import.alias_ersetzt', ['n' => $aliasErsetzt])) ?>
    <?php endif; ?>
</p>

<div class="kennzahlen">
    <div class="karte"><strong><?= count($abgleich['neu']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_neu')) ?></span></div>
    <div class="karte"><strong><?= count($abgleich['geaendert']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_aktualisiert')) ?></span></div>
    <div class="karte"><strong><?= $abgleich['unveraendert'] ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_unveraendert')) ?></span></div>
    <?php if ($abgleich['geschuetzt']): ?><div class="karte"><strong><?= count($abgleich['geschuetzt']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_bearb')) ?></span></div><?php endif; ?>
    <div class="karte"><strong><?= count($abgleich['fehlerBild']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_bild_fehlt')) ?></span></div>
    <?php if ($abgleich['imPapierkorb']): ?><div class="karte"><strong><?= count($abgleich['imPapierkorb']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_papierkorb')) ?></span></div><?php endif; ?>
</div>

<?php if ($nichtZugeordnet): ?>
    <div class="flash flash--hinweis mt-m"><?= Helpers::e(t('import.nicht_zugeordnet', ['felder' => implode(', ', array_map(static fn($f) => TabellenImport::zielfelder()[$f] ?? $f, $nichtZugeordnet))])) ?></div>
<?php endif; ?>

<div class="feldreihe mt-l">
    <div class="karte" style="flex:1;min-width:300px;">
        <h3><?= Helpers::e(t('import.neue_werke')) ?></h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['neu']); ?></ul>
    </div>
    <div class="karte" style="flex:1;min-width:300px;">
        <h3><?= Helpers::e(t('import.aenderungen_titel')) ?></h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['geaendert'], 25, true); ?></ul>
    </div>
</div>

<?php if ($abgleich['geschuetzt']): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('import.bearb_titel', ['n' => count($abgleich['geschuetzt'])])) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('import.bearb_beschr')) ?></p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['geschuetzt'], 50, true); ?></ul>
</div>
<?php endif; ?>

<?php if ($abgleich['fehlerBild']): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('import.bild_fehlt_titel')) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('import.bild_fehlt_beschr')) ?></p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['fehlerBild'], 50); ?></ul>
</div>
<?php endif; ?>

<?php if ($ungueltigeDateinamen): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('import.ungueltige_titel')) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('import.ungueltige_beschr', ['endungen' => implode(', ', BILD_ENDUNGEN)])) ?></p>
    <ul class="text-klein"><?php $zeigeListe($ungueltigeDateinamen, 50); ?></ul>
</div>
<?php endif; ?>

<?php if ($abgleich['imPapierkorb']): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('import.papierkorb_titel', ['n' => count($abgleich['imPapierkorb'])])) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('import.papierkorb_beschr')) ?></p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['imPapierkorb'], 25); ?></ul>
</div>
<?php endif; ?>

<?php if ($abgleich['duplikate']): ?>
<div class="karte mt-m">
    <h3><?= Helpers::e(t('import.duplikate_titel')) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('import.duplikate_beschr')) ?></p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['duplikate'], 50); ?></ul>
</div>
<?php endif; ?>

<form method="post" action="/import.php" class="mt-l">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="uebernehmen">
    <input type="hidden" name="tmp_datei" value="<?= Helpers::e($tmp_datei) ?>">
    <?php foreach ($mapping as $index => $ziel): ?>
        <input type="hidden" name="mapping[<?= (int) $index ?>]" value="<?= Helpers::e($ziel) ?>">
    <?php endforeach; ?>
    <?php if ($abgleich['geschuetzt']): ?>
        <div class="feld">
            <label style="display:inline-flex;gap:6px;align-items:center;color:var(--farbe-text);font-size:var(--schrift-groesse-basis);">
                <input type="checkbox" name="bearbeitete_ueberschreiben" value="1">
                <?= Helpers::e(t('import.bearb_ueberschreiben', ['n' => count($abgleich['geschuetzt'])])) ?>
            </label>
        </div>
    <?php endif; ?>
    <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('import.jetzt_importieren')) ?></button>
    <a href="/import.php" class="btn"><?= Helpers::e(t('allg.abbrechen')) ?></a>
</form>
