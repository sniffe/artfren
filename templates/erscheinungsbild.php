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
    'galerie'  => ['label' => 'Galerie', 'beschr' => 'Warme Töne, Serifen-Überschriften (Standard)'],
    'archiv'   => ['label' => 'Archiv',  'beschr' => 'Kühle Grautöne, kompaktere Darstellung'],
    'kontrast' => ['label' => 'Kontrast','beschr' => 'Hoher Kontrast, größere Schrift'],
];
?>
<h2>Erscheinungsbild</h2>

<form method="post">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="speichern">

    <!-- ── Look intern ──────────────────────────────────────────────── -->
    <div class="karte" style="max-width:820px;">
        <h3>Design – interner Bereich</h3>
        <div class="feld">
            <label>Look</label>
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
            <label>Schrift</label>
            <div class="checkliste">
                <label><input type="radio" name="schrift" value="serif"   <?= $schrift === 'serif'   ? 'checked' : '' ?>> Serif (Source Serif 4 für Überschriften)</label>
                <label><input type="radio" name="schrift" value="grotesk" <?= $schrift === 'grotesk' ? 'checked' : '' ?>> Grotesque (Inter für alles)</label>
                <label><input type="radio" name="schrift" value="system"  <?= $schrift === 'system'  ? 'checked' : '' ?>> System-Schrift</label>
            </div>
        </div>

        <div class="feld">
            <label>Icon-Stärke</label>
            <div class="checkliste">
                <label><input type="radio" name="icon_staerke" value="regular" <?= $iconStaerke === 'regular' ? 'checked' : '' ?>> Normal</label>
                <label><input type="radio" name="icon_staerke" value="light"   <?= $iconStaerke === 'light'   ? 'checked' : '' ?>> Leicht</label>
                <label><input type="radio" name="icon_staerke" value="bold"    <?= $iconStaerke === 'bold'    ? 'checked' : '' ?>> Fett</label>
            </div>
        </div>
    </div>

    <!-- ── Look Web ─────────────────────────────────────────────────── -->
    <div class="karte" style="max-width:820px;">
        <h3>Design – öffentliche Galerien</h3>
        <div class="feld">
            <label>Look</label>
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
        <h3>Akzentfarbe</h3>
        <p class="text-klein text-sekundaer">Gilt für beide Bereiche. Leer = Standardfarbe (Bordeaux). Die Textfarbe auf der Akzentfarbe wird automatisch für guten Kontrast berechnet.</p>
        <div class="feldreihe" style="align-items:flex-end;">
            <div class="feld" style="flex:0 0 auto;">
                <label for="akzentfarbe_picker">Farbwähler</label>
                <input type="color" id="akzentfarbe_picker" value="<?= Helpers::e($akzentfarbe !== '' ? $akzentfarbe : '#7A2A38') ?>"
                       style="width:60px; height:38px; padding:2px; cursor:pointer; border:1px solid var(--farbe-linie);"
                       data-farb-sync="akzentfarbe">
            </div>
            <div class="feld">
                <label for="akzentfarbe">Hex-Wert <span class="text-sekundaer">(#RRGGBB, leer = Standard)</span></label>
                <input type="text" id="akzentfarbe" name="akzentfarbe"
                       value="<?= Helpers::e($akzentfarbe) ?>"
                       placeholder="#7A2A38" maxlength="7" pattern="#[0-9A-Fa-f]{6}"
                       style="font-family:monospace;">
            </div>
        </div>
    </div>

    <div style="max-width:820px; margin-top:var(--abstand-m);">
        <button type="submit" class="btn btn--primaer">Einstellungen speichern</button>
    </div>
</form>

<!-- ── Logo ─────────────────────────────────────────────────────────── -->
<div class="karte" style="max-width:820px;">
    <h3>Logo</h3>
    <p class="text-klein text-sekundaer">PNG, JPG, WebP oder SVG, max. 1 MB. Wird im Kopfbereich angezeigt; ohne Logo erscheint der App-Name als Text.</p>

    <?php if ($hatLogo): ?>
        <div style="margin-bottom:var(--abstand-m);">
            <img src="/logo.php" alt="Aktuelles Logo" style="max-height:60px; max-width:240px; border:1px solid var(--farbe-linie); padding:4px;">
        </div>
        <form method="post" data-bestaetigen="Logo wirklich entfernen?">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="logo_loeschen">
            <button type="submit" class="btn btn--gefahr btn--klein">Logo entfernen</button>
        </form>
        <hr style="margin:var(--abstand-m) 0; border:none; border-top:1px solid var(--farbe-linie);">
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="logo_hochladen">
        <div class="feld">
            <label for="logo-upload">Logo <?= $hatLogo ? 'ersetzen' : 'hochladen' ?></label>
            <input type="file" id="logo-upload" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        </div>
        <button type="submit" class="btn">Hochladen</button>
    </form>
</div>
