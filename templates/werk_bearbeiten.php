<?php
/** @var array $werk */
/** @var array $eingabe */
/** @var string[] $fehler */
/** @var string $zurueck */
/** @var array|null $hauptbild */
/** @var string[] $orte */
/** @var string[] $malerListe */
/** @var string[] $freieBilder */
declare(strict_types=1);

use App\Helpers;

$id = (int) $werk['id'];
$betrag = static fn($w): string => $w === null ? '' : number_format((float) $w, 2, ',', '.');
$bildUrl = $hauptbild ? Helpers::bildUrl((int) $hauptbild['id'], $hauptbild['dateiname'], 'm') : null;
$feld = static function (string $name, string $label, string $wert, string $extra = '') : void {
    echo '<div class="feld"><label for="' . $name . '">' . $label . '</label>'
        . '<input type="text" id="' . $name . '" name="' . $name . '" value="' . Helpers::e($wert) . '" ' . $extra . '></div>';
};
?>
<p class="text-klein"><a href="<?= Helpers::e($zurueck) ?>">← zurück</a></p>
<h2>Werk bearbeiten</h2>

<?php if ($fehler): ?>
    <div class="flash flash--fehler">
        <?php foreach ($fehler as $f): ?><div><?= Helpers::e($f) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($werk['bearbeitet_am']): ?>
    <p class="text-klein text-sekundaer">Zuletzt im Programm bearbeitet am <?= Helpers::e(Helpers::formatDatum($werk['bearbeitet_am'])) ?>. Ein späterer Tabellen-Import überschreibt dieses Werk nur nach ausdrücklicher Bestätigung.</p>
<?php endif; ?>

<form method="post" action="/werk_bearbeiten.php" enctype="multipart/form-data">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="zurueck" value="<?= Helpers::e($zurueck) ?>">

    <div class="bearbeiten-raster">
        <div class="karte">
            <div class="feldreihe">
                <?php $feld('maler', 'Maler', (string) $eingabe['maler'], 'list="liste-maler" maxlength="500"'); ?>
                <?php $feld('titel', 'Titel', (string) $eingabe['titel'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('ort', 'Ort', (string) $eingabe['ort'], 'list="liste-orte" maxlength="500"'); ?>
                <div class="feld">
                    <label for="werktyp">Typ</label>
                    <select id="werktyp" name="werktyp">
                        <option value="Bild" <?= $eingabe['werktyp'] !== 'Objekt' ? 'selected' : '' ?>>Bild</option>
                        <option value="Objekt" <?= $eingabe['werktyp'] === 'Objekt' ? 'selected' : '' ?>>Objekt</option>
                    </select>
                </div>
            </div>
            <div class="feldreihe">
                <?php $feld('format', 'Format', (string) $eingabe['format'], 'maxlength="500" placeholder="z. B. 120x100"'); ?>
                <?php $feld('technik', 'Technik', (string) $eingabe['technik'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('entstehungsjahr', 'Entstehungsjahr', (string) $eingabe['entstehungsjahr'], 'inputmode="numeric" maxlength="20"'); ?>
                <?php $feld('ankaufjahr', 'Ankaufjahr', (string) $eingabe['ankaufjahr'], 'inputmode="numeric" maxlength="20"'); ?>
                <?php $feld('ankauf', 'Ankauf (bei wem)', (string) $eingabe['ankauf'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('ankaufswert', 'Ankaufswert (€)', $betrag($eingabe['ankaufswert']), 'inputmode="decimal" maxlength="30"'); ?>
                <?php $feld('wert', 'Wert (€)', $betrag($eingabe['wert']), 'inputmode="decimal" maxlength="30"'); ?>
                <div class="feld">
                    <label for="status_farbe">Status</label>
                    <select id="status_farbe" name="status_farbe">
                        <option value="">keine Markierung</option>
                        <?php foreach (Helpers::STATUS_FARBEN as $wert => $label): ?>
                            <option value="<?= $wert ?>" <?= $eingabe['status_farbe'] === $wert ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="karte">
            <h3>Bild</h3>
            <div class="passepartout">
                <?php if ($bildUrl): ?>
                    <img src="<?= Helpers::e($bildUrl) ?>" alt="">
                <?php elseif ($hauptbild): ?>
                    <span class="passepartout--leer">Datei „<?= Helpers::e($hauptbild['dateiname']) ?>“ fehlt</span>
                <?php else: ?>
                    <span class="passepartout--leer">Kein Bild</span>
                <?php endif; ?>
            </div>
            <?php if ($hauptbild): ?>
                <p class="text-klein text-sekundaer"><?= Helpers::e($hauptbild['dateiname']) ?></p>
            <?php endif; ?>

            <div class="feld mt-m">
                <label for="bild">Neues Bild hochladen</label>
                <input type="file" id="bild" name="bild" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
            </div>
            <div class="feld">
                <label for="vorhandenes_bild">… oder Datei aus dem Bilder-Ordner zuweisen</label>
                <input type="text" id="vorhandenes_bild" name="vorhandenes_bild" list="liste-bilder" autocomplete="off"
                       placeholder="<?= $freieBilder ? count($freieBilder) . ' noch nicht zugeordnete Bilder – Namen tippen' : 'Dateiname' ?>">
            </div>
            <?php if ($hauptbild): ?>
                <label style="display:inline-flex;gap:6px;align-items:center;">
                    <input type="checkbox" name="bild_entfernen" value="1"> Bildzuordnung entfernen
                </label>
            <?php endif; ?>
        </div>
    </div>

    <div class="toolbar-aktionen mt-m">
        <button type="submit" class="btn btn--primaer">Speichern</button>
        <a href="<?= Helpers::e($zurueck) ?>" class="btn">Abbrechen</a>
    </div>
</form>

<datalist id="liste-orte"><?php foreach ($orte as $o): ?><option value="<?= Helpers::e($o) ?>"><?php endforeach; ?></datalist>
<datalist id="liste-maler"><?php foreach ($malerListe as $m): ?><option value="<?= Helpers::e($m) ?>"><?php endforeach; ?></datalist>
<datalist id="liste-bilder"><?php foreach ($freieBilder as $b): ?><option value="<?= Helpers::e($b) ?>"><?php endforeach; ?></datalist>
