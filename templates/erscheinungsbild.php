<?php
/** @var string $lookIntern */
/** @var string $lookWeb */
/** @var string $akzentfarbe */
/** @var string $schrift */
/** @var string $iconStaerke */
/** @var bool $hatLogo */
declare(strict_types=1);

use App\Helpers;

$looks = [
    'galerie'  => ['label' => t('erscheint.look_galerie'),  'beschr' => t('erscheint.look_galerie_beschr')],
    'archiv'   => ['label' => t('erscheint.look_archiv'),   'beschr' => t('erscheint.look_archiv_beschr')],
    'kontrast' => ['label' => t('erscheint.look_kontrast'), 'beschr' => t('erscheint.look_kontrast_beschr')],
];
?>
<h2><?= Helpers::e(t('erscheint.titel')) ?></h2>

<form method="post">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="speichern">

    <!-- ── Look intern ──────────────────────────────────────────────── -->
    <div class="karte" style="max-width:820px;">
        <h3><?= Helpers::e(t('erscheint.design_intern')) ?></h3>
        <div class="feld">
            <label><?= Helpers::e(t('erscheint.look')) ?></label>
            <div class="checkliste">
                <?php foreach ($looks as $wert => $info): ?>
                    <label>
                        <input type="radio" name="look_intern" value="<?= Helpers::e($wert) ?>"
                               <?= $lookIntern === $wert ? 'checked' : '' ?>>
                        <strong><?= Helpers::e($info['label']) ?></strong>
                        <span class="text-sekundaer text-klein"> – <?= Helpers::e($info['beschr']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="feld">
            <label><?= Helpers::e(t('erscheint.schrift')) ?></label>
            <div class="checkliste">
                <label><input type="radio" name="schrift" value="serif"   <?= $schrift === 'serif'   ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.schrift_serif')) ?></label>
                <label><input type="radio" name="schrift" value="grotesk" <?= $schrift === 'grotesk' ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.schrift_grotesk')) ?></label>
                <label><input type="radio" name="schrift" value="system"  <?= $schrift === 'system'  ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.schrift_system')) ?></label>
            </div>
        </div>

        <div class="feld">
            <label><?= Helpers::e(t('erscheint.icon_staerke')) ?></label>
            <div class="checkliste">
                <label><input type="radio" name="icon_staerke" value="regular" <?= $iconStaerke === 'regular' ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.icon_regular')) ?></label>
                <label><input type="radio" name="icon_staerke" value="light"   <?= $iconStaerke === 'light'   ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.icon_light')) ?></label>
                <label><input type="radio" name="icon_staerke" value="bold"    <?= $iconStaerke === 'bold'    ? 'checked' : '' ?>> <?= Helpers::e(t('erscheint.icon_bold')) ?></label>
            </div>
        </div>
    </div>

    <!-- ── Look Web ─────────────────────────────────────────────────── -->
    <div class="karte" style="max-width:820px;">
        <h3><?= Helpers::e(t('erscheint.design_web')) ?></h3>
        <div class="feld">
            <label><?= Helpers::e(t('erscheint.look')) ?></label>
            <div class="checkliste">
                <?php foreach ($looks as $wert => $info): ?>
                    <label>
                        <input type="radio" name="look_web" value="<?= Helpers::e($wert) ?>"
                               <?= $lookWeb === $wert ? 'checked' : '' ?>>
                        <strong><?= Helpers::e($info['label']) ?></strong>
                        <span class="text-sekundaer text-klein"> – <?= Helpers::e($info['beschr']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Akzentfarbe ───────────────────────────────────────────────── -->
    <div class="karte" style="max-width:820px;">
        <h3><?= Helpers::e(t('erscheint.akzentfarbe')) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('erscheint.akzentfarbe_beschr')) ?></p>
        <div class="feldreihe" style="align-items:flex-end;">
            <div class="feld" style="flex:0 0 auto;">
                <label for="akzentfarbe_picker"><?= Helpers::e(t('erscheint.farbwaehler')) ?></label>
                <input type="color" id="akzentfarbe_picker" value="<?= Helpers::e($akzentfarbe !== '' ? $akzentfarbe : '#7A2A38') ?>"
                       style="width:60px; height:38px; padding:2px; cursor:pointer; border:1px solid var(--farbe-linie);"
                       data-farb-sync="akzentfarbe">
            </div>
            <div class="feld">
                <label for="akzentfarbe"><?= Helpers::e(t('erscheint.hex_wert')) ?> <span class="text-sekundaer"><?= Helpers::e(t('erscheint.hex_hinweis')) ?></span></label>
                <input type="text" id="akzentfarbe" name="akzentfarbe"
                       value="<?= Helpers::e($akzentfarbe) ?>"
                       placeholder="#7A2A38" maxlength="7" pattern="#[0-9A-Fa-f]{6}"
                       style="font-family:monospace;">
            </div>
        </div>
    </div>

    <div style="max-width:820px; margin-top:var(--abstand-m);">
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('erscheint.einst_speichern')) ?></button>
    </div>
</form>

<!-- ── Logo ─────────────────────────────────────────────────────────── -->
<div class="karte" style="max-width:820px;">
    <h3><?= Helpers::e(t('erscheint.logo')) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('erscheint.logo_beschr')) ?></p>

    <?php if ($hatLogo): ?>
        <div style="margin-bottom:var(--abstand-m);">
            <img src="/logo.php" alt="<?= Helpers::e(t('erscheint.logo')) ?>" style="max-height:60px; max-width:240px; border:1px solid var(--farbe-linie); padding:4px;">
        </div>
        <form method="post" data-bestaetigen="<?= Helpers::e(t('erscheint.logo_entf_frage')) ?>">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="logo_loeschen">
            <button type="submit" class="btn btn--gefahr btn--klein"><?= Helpers::e(t('erscheint.logo_entfernen')) ?></button>
        </form>
        <hr style="margin:var(--abstand-m) 0; border:none; border-top:1px solid var(--farbe-linie);">
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="logo_hochladen">
        <div class="feld">
            <label for="logo-upload"><?= Helpers::e($hatLogo ? t('erscheint.logo_ersetzen') : t('erscheint.logo_hochladen')) ?></label>
            <input type="file" id="logo-upload" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        </div>
        <button type="submit" class="btn"><?= Helpers::e(t('erscheint.hochladen')) ?></button>
    </form>
</div>
