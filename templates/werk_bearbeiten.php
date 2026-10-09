<?php
/** @var array $werk */
/** @var array $eingabe */
/** @var string[] $fehler */
/** @var string $zurueck */
/** @var array[] $bilder */
/** @var string[] $orte */
/** @var string[] $malerListe */
/** @var string[] $freieBilder */
/** @var bool $neu */
declare(strict_types=1);

use App\Helpers;

$id = (int) $werk['id'];
$betrag = static fn($w): string => $w === null ? '' : number_format((float) $w, 2, ',', '.');
$feld = static function (string $name, string $label, string $wert, string $extra = '') : void {
    echo '<div class="feld"><label for="' . $name . '">' . Helpers::e($label) . '</label>'
        . '<input type="text" id="' . $name . '" name="' . $name . '" value="' . Helpers::e($wert) . '" ' . $extra . '></div>';
};
?>
<p class="text-klein"><a href="<?= Helpers::e($zurueck) ?>"><?= Helpers::e(t('allg.zurueck')) ?></a></p>
<h2><?= Helpers::e($neu ? t('werk_bearb.neu_titel') : t('werk_bearb.bearb_titel')) ?></h2>

<?php if ($fehler): ?>
    <div class="flash flash--fehler">
        <?php foreach ($fehler as $f): ?><div><?= Helpers::e($f) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($werk['bearbeitet_am']): ?>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('werk_bearb.bearbeitet_am', ['datum' => Helpers::formatDatum($werk['bearbeitet_am'])])) ?></p>
<?php endif; ?>

<form method="post" action="/werk_bearbeiten.php" enctype="multipart/form-data">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="zurueck" value="<?= Helpers::e($zurueck) ?>">

    <div class="bearbeiten-raster">
        <div class="karte">
            <div class="feldreihe">
                <?php $feld('maler', t('feld.maler'), (string) $eingabe['maler'], 'list="liste-maler" maxlength="500"' . ($neu ? ' autofocus' : '')); ?>
                <?php $feld('titel', t('feld.titel'), (string) $eingabe['titel'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('ort', t('feld.ort'), (string) $eingabe['ort'], 'list="liste-orte" maxlength="500"'); ?>
                <div class="feld">
                    <label for="werktyp"><?= Helpers::e(t('werk_bearb.typ_label')) ?></label>
                    <select id="werktyp" name="werktyp">
                        <option value="Bild" <?= $eingabe['werktyp'] !== 'Objekt' ? 'selected' : '' ?>><?= Helpers::e(t('werk_bearb.typ_bild')) ?></option>
                        <option value="Objekt" <?= $eingabe['werktyp'] === 'Objekt' ? 'selected' : '' ?>><?= Helpers::e(t('werk_bearb.typ_objekt')) ?></option>
                    </select>
                </div>
            </div>
            <div class="feldreihe">
                <?php $feld('format', t('feld.format'), (string) $eingabe['format'], 'maxlength="500" placeholder="z. B. 120x100"'); ?>
                <?php $feld('technik', t('feld.technik'), (string) $eingabe['technik'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('entstehungsjahr', t('feld.entstehungsjahr'), (string) $eingabe['entstehungsjahr'], 'inputmode="numeric" maxlength="20"'); ?>
                <?php $feld('ankaufjahr', t('feld.ankaufjahr'), (string) $eingabe['ankaufjahr'], 'inputmode="numeric" maxlength="20"'); ?>
                <?php $feld('ankauf', t('feld.ankauf'), (string) $eingabe['ankauf'], 'maxlength="500"'); ?>
            </div>
            <div class="feldreihe">
                <?php $feld('ankaufswert', t('feld.ankaufswert'), $betrag($eingabe['ankaufswert']), 'inputmode="decimal" maxlength="30"'); ?>
                <?php $feld('wert', t('feld.wert'), $betrag($eingabe['wert']), 'inputmode="decimal" maxlength="30"'); ?>
                <div class="feld">
                    <label for="status_farbe"><?= Helpers::e(t('werk_bearb.status_label')) ?></label>
                    <select id="status_farbe" name="status_farbe">
                        <option value=""><?= Helpers::e(t('werk_bearb.keine_markierung')) ?></option>
                        <?php foreach (Helpers::STATUS_FARBEN as $wert => $label): ?>
                            <option value="<?= $wert ?>" <?= $eingabe['status_farbe'] === $wert ? 'selected' : '' ?>><?= Helpers::e(t('status.' . $wert)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="feld mt-m">
                <label for="beschreibung_oeffentlich"><?= Helpers::e(t('feld.beschreibung_oeffentlich')) ?></label>
                <textarea id="beschreibung_oeffentlich" name="beschreibung_oeffentlich" rows="4" maxlength="5000"><?= Helpers::e((string) ($eingabe['beschreibung_oeffentlich'] ?? '')) ?></textarea>
            </div>
            <div class="feld">
                <label for="beschreibung_intern"><?= Helpers::e(t('feld.beschreibung_intern')) ?> <span class="text-sekundaer text-klein"><?= Helpers::e(t('werk_bearb.beschr_intern_hinweis')) ?></span></label>
                <textarea id="beschreibung_intern" name="beschreibung_intern" rows="3" maxlength="5000"><?= Helpers::e((string) ($eingabe['beschreibung_intern'] ?? '')) ?></textarea>
            </div>
            <div class="feldreihe">
                <?php $feld('herkunft', t('feld.herkunft'), (string) ($eingabe['herkunft'] ?? ''), 'maxlength="500"'); ?>
                <?php $feld('copyright', t('feld.copyright'), (string) ($eingabe['copyright'] ?? ''), 'maxlength="500"'); ?>
            </div>
            <div class="feld">
                <label>
                    <input type="checkbox" name="web_freigabe" value="1" <?= !empty($eingabe['web_freigabe']) ? 'checked' : '' ?>>
                    <?= Helpers::e(t('werk_bearb.web_freigabe')) ?>
                </label>
            </div>
        </div>

        <div class="karte">
            <h3><?= Helpers::e(t('werk_bearb.bilder')) ?></h3>

            <?php if ($bilder !== []): ?>
                <div class="bild-liste" id="bild-reihenfolge-liste">
                    <?php foreach ($bilder as $bild): ?>
                        <?php
                        $bId = (int) $bild['id'];
                        $bUrl = Helpers::bildUrl($bId, $bild['dateiname'], 'm');
                        ?>
                        <div class="bild-karte" data-bild-id="<?= $bId ?>">
                            <input type="hidden" name="bild_reihenfolge[]" value="<?= $bId ?>">
                            <div class="passepartout bild-karte__vorschau">
                                <?php if ($bUrl): ?>
                                    <img src="<?= Helpers::e($bUrl) ?>" alt="">
                                <?php else: ?>
                                    <span class="passepartout--leer"><?= Helpers::e(t('werk_bearb.bild_fehlt')) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="bild-karte__meta">
                                <?php if ($bild['ist_hauptbild']): ?>
                                    <span class="badge"><?= Helpers::e(t('werk_bearb.hauptbild_badge')) ?></span>
                                <?php endif; ?>
                                <p class="text-klein text-sekundaer"><?= Helpers::e($bild['dateiname']) ?></p>
                                <div class="feld">
                                    <label for="bbs-<?= $bId ?>"><?= Helpers::e(t('werk_bearb.beschriftung')) ?></label>
                                    <input type="text" id="bbs-<?= $bId ?>" name="bild_beschriftung[<?= $bId ?>]"
                                           value="<?= Helpers::e((string) ($bild['beschriftung'] ?? '')) ?>" maxlength="500">
                                </div>
                                <label class="text-klein" style="display:flex;gap:4px;align-items:center;margin-top:6px;">
                                    <input type="checkbox" name="bild_entfernen[]" value="<?= $bId ?>">
                                    <?= Helpers::e(t('werk_bearb.bild_entfernen')) ?>
                                </label>
                            </div>
                            <div class="bild-karte__reihenfolge">
                                <button type="submit" name="bild_verschieben_oben[<?= $bId ?>]" value="1" class="btn btn--klein" title="<?= Helpers::e(t('werk_bearb.nach_oben')) ?>">↑</button>
                                <button type="submit" name="bild_verschieben_unten[<?= $bId ?>]" value="1" class="btn btn--klein" title="<?= Helpers::e(t('werk_bearb.nach_unten')) ?>">↓</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-klein text-sekundaer mt-m"><?= Helpers::e(t('werk_bearb.bilder_zaehler', ['n' => count($bilder), 'max' => MAX_BILDER_PRO_WERK])) ?></p>
            <?php elseif (!$neu): ?>
                <p class="text-sekundaer"><?= Helpers::e(t('werk_bearb.kein_bild')) ?></p>
            <?php endif; ?>

            <?php if (count($bilder) < MAX_BILDER_PRO_WERK): ?>
                <div class="mt-m">
                    <?php if ($neu): ?>
                        <div class="feld">
                            <label for="bild"><?= Helpers::e(t('werk_bearb.hauptbild_upload')) ?></label>
                            <input type="file" id="bild" name="bild" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                        </div>
                    <?php endif; ?>
                    <div class="feld">
                        <label for="bilder_neu"><?= Helpers::e($neu ? t('werk_bearb.weitere_bilder') : t('werk_bearb.bilder_hinzufuegen')) ?></label>
                        <input type="file" id="bilder_neu" name="bilder_neu[]" accept=".jpg,.jpeg,.png,.gif,.webp,image/*" multiple>
                    </div>
                    <div class="feld">
                        <label for="vorhandenes_bild"><?= Helpers::e(t('werk_bearb.aus_bilder_ordner')) ?></label>
                        <input type="text" id="vorhandenes_bild" name="vorhandenes_bild" list="liste-bilder" autocomplete="off"
                               placeholder="<?= $freieBilder ? Helpers::e(t('werk_bearb.placeholder_viele', ['n' => count($freieBilder)])) : Helpers::e(t('werk_bearb.placeholder_leer')) ?>">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="toolbar mt-m">
        <div class="toolbar-aktionen">
            <button type="submit" class="btn btn--primaer"><?= Helpers::e($neu ? t('werk_bearb.anlegen_btn') : t('werk_bearb.speichern_btn')) ?></button>
            <a href="<?= Helpers::e($zurueck) ?>" class="btn"><?= Helpers::e(t('allg.abbrechen')) ?></a>
        </div>
        <?php if (!$neu): ?>
            <?php /* Gehört zum eigenen Formular unten (form-Attribut), da Formulare nicht verschachtelt werden dürfen. */ ?>
            <button type="submit" form="werk-papierkorb" class="btn btn--klein btn--gefahr"><?= Helpers::icon('papierkorb') ?> <?= Helpers::e(t('werk.in_papierkorb')) ?></button>
        <?php endif; ?>
    </div>
</form>

<?php if (!$neu): ?>
<form method="post" action="/papierkorb.php" id="werk-papierkorb" hidden
      data-bestaetigen="<?= Helpers::e(t('werk.papierkorb_frage', ['titel' => $werk['titel'] ?? t('werk.unbenannt')])) ?>">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="bulk_papierkorb">
    <input type="hidden" name="werk_ids[]" value="<?= (int) $werk['id'] ?>">
</form>
<?php endif; ?>

<datalist id="liste-orte"><?php foreach ($orte as $o): ?><option value="<?= Helpers::e($o) ?>"><?php endforeach; ?></datalist>
<datalist id="liste-maler"><?php foreach ($malerListe as $m): ?><option value="<?= Helpers::e($m) ?>"><?php endforeach; ?></datalist>
<datalist id="liste-bilder"><?php foreach ($freieBilder as $b): ?><option value="<?= Helpers::e($b) ?>"><?php endforeach; ?></datalist>
