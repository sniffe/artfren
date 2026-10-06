<?php
/** @var array $gruppe */
/** @var array $werke */
/** @var array $summen */
/** @var bool $istAdmin */
/** @var array $freigegebenFuer */
/** @var string[] $auswahlVerwerfen */
/** @var int $bilderAnzahl */
/** @var array[] $werkeSortiert */
/** @var array $webStats */
/** @var array[] $webFeldOptionen */
/** @var string[] $erlaubteFelder */
/** @var string $baseUrl */
/** @var array[] $pdfProfile */
declare(strict_types=1);

use App\ExportProfile;
use App\Felder;
use App\Helpers;

$gruppeId = (int) $gruppe['id'];
?>
<div class="toolbar">
    <div>
        <p class="text-klein text-sekundaer"><a href="/gruppen.php"><?= Helpers::e(t('gruppe.zurueck')) ?></a></p>
        <h2 class="gruppenname"><?= Helpers::e($gruppe['name']) ?></h2>
        <p class="text-sekundaer text-klein">
            <?= Helpers::e(\App\I18n::plural(count($werke), 'gruppe.werke_zaehler', ['n' => count($werke)])) ?>
            <?php if ((float) ($summen['ankaufswert'] ?? 0) > 0): ?> · <?= Helpers::e(t('gruppe.summe_ankaufswert', ['summe' => Helpers::formatGeld((float) $summen['ankaufswert'])])) ?><?php endif; ?>
            <?php if ((float) ($summen['wert'] ?? 0) > 0): ?> · <?= Helpers::e(t('gruppe.summe_wert', ['summe' => Helpers::formatGeld((float) $summen['wert'])])) ?><?php endif; ?>
            · <?= Helpers::e(t('gruppe.erstellt_am', ['datum' => Helpers::formatDatum($gruppe['erstellt_am'], 'd.m.Y')])) ?>
            <?php if ($istAdmin): ?>
                · <?= Helpers::e(t('gruppe.sichtbar_fuer')) ?>
                <?php if ($freigegebenFuer): ?>
                    <?php foreach ($freigegebenFuer as $i => $b): ?><?= $i > 0 ? ', ' : '' ?><a href="/benutzer_bearbeiten.php?id=<?= (int) $b['id'] ?>"><?= Helpers::e($b['echter_name'] ?: $b['benutzername']) ?></a><?php endforeach; ?>
                <?php else: ?>
                    <?= Helpers::e(t('gruppe.nur_admins')) ?>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
    <?php
    $darfExcel  = $istAdmin || ($aktuellerBenutzer['darf_excel']          ?? false);
    $darfBilder = $istAdmin || ($aktuellerBenutzer['darf_bilder_export']  ?? false);
    ?>
    <div class="toolbar-aktionen">
        <?php if ($darfExcel): ?>
            <a href="/export_gruppe.php?id=<?= $gruppeId ?>&amp;format=xlsx" class="btn" title="<?= Helpers::e(t('gruppen.excel_titel')) ?>"><?= Helpers::e(t('gruppe.excel')) ?></a>
        <?php endif; ?>
        <?php if ($bilderAnzahl > 0 && $darfBilder): ?>
            <a href="/export_gruppe.php?id=<?= $gruppeId ?>&amp;format=bilder" class="btn" title="<?= Helpers::e(t('gruppen.bilder_titel')) ?>"><?= Helpers::e(t('gruppe.bilder_zip', ['n' => $bilderAnzahl])) ?></a>
        <?php endif; ?>
        <button type="button" class="btn" data-pdf-dialog="<?= $gruppeId ?>"><?= Helpers::e(t('gruppe.pdf')) ?></button>
        <?php if ($istAdmin): ?>
            <a href="/werke.php?gruppe_id=<?= $gruppeId ?>" class="btn btn--primaer"><?= Helpers::e(t('gruppe.werke_hinzufuegen')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="vitrine-karten">
    <?php foreach ($werke as $w): $bild = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 'm'); ?>
        <a class="vitrine-karte" href="/werk.php?id=<?= (int) $w['id'] ?>">
            <div class="passepartout">
                <?php if ($bild): ?>
                    <img src="<?= Helpers::e($bild) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <span class="passepartout--leer"><?= Helpers::e(t('gruppe.kein_bild')) ?></span>
                <?php endif; ?>
            </div>
            <div class="werk-etikett">
                <div class="maler"><?= Helpers::e($w['maler']) ?></div>
                <div class="titel"><?= Helpers::e($w['titel']) ?></div>
                <div class="meta"><?= Helpers::e($w['technik']) ?><?= $w['entstehungsjahr'] ? ', ' . (int) $w['entstehungsjahr'] : '' ?></div>
            </div>
        </a>
    <?php endforeach; ?>
    <?php if (!$werke): ?>
        <p class="text-sekundaer"><?= Helpers::e(t('gruppe.leer')) ?></p>
    <?php endif; ?>
</div>

<?php if ($istAdmin): ?>

<!-- ── Web-Veröffentlichung ───────────────────────────────────────── -->
<div class="karte mt-l" style="max-width:900px;">
    <h3><?= Helpers::e(t('gruppe.web_veroeffentlichen')) ?></h3>

    <?php if ($gruppe['web_aktiv']): ?>
        <?php $galerieUrl = $baseUrl . '/w/' . $gruppe['web_token']; ?>
        <p>
            <span class="badge badge--admin"><?= Helpers::e(t('gruppe.web_veroeffentlicht')) ?></span>
            <?php if (!empty($gruppe['web_ablauf'])): ?>
                · <?= Helpers::e(t('gruppe.web_laeuft_ab', ['datum' => Helpers::formatDatum($gruppe['web_ablauf'], 'd.m.Y')])) ?>
            <?php endif; ?>
            <?php if (!empty($gruppe['web_passwort_hash'])): ?> · <?= Helpers::e(t('gruppe.web_passwortgeschuetzt')) ?><?php endif; ?>
            <?php if (!empty($gruppe['web_aufrufe'])): ?> · <?= Helpers::e(t('gruppe.web_aufrufe', ['n' => (int) $gruppe['web_aufrufe']])) ?><?php endif; ?>
        </p>
        <div style="display:flex; gap:var(--abstand-s); align-items:center; flex-wrap:wrap; margin-bottom:var(--abstand-m);">
            <code id="web-galerie-url" style="flex:1; min-width:200px; overflow-wrap:anywhere;"><?= Helpers::e($galerieUrl) ?></code>
            <button type="button" class="btn btn--klein" data-kopieren="web-galerie-url"><?= Helpers::e(t('gruppe.web_kopieren')) ?></button>
            <a href="<?= Helpers::e($galerieUrl) ?>" target="_blank" rel="noopener" class="btn btn--klein"><?= Helpers::e(t('gruppe.web_oeffnen')) ?></a>
        </div>
        <div style="display:flex; gap:var(--abstand-s); flex-wrap:wrap; margin-bottom:var(--abstand-l);">
            <form method="post">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="web_deaktivieren">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--gefahr btn--klein"
                        data-bestaetigen="<?= Helpers::e(t('gruppe.web_zurueckziehen_frage')) ?>"><?= Helpers::e(t('gruppe.web_zurueckziehen')) ?></button>
            </form>
            <form method="post">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="web_link_erneuern">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--klein"
                        data-bestaetigen="<?= Helpers::e(t('gruppe.web_link_erneuern_frage')) ?>"><?= Helpers::e(t('gruppe.web_link_erneuern')) ?></button>
            </form>
        </div>
    <?php else: ?>
        <p class="text-sekundaer text-klein"><?= Helpers::e(t('gruppe.web_nicht_oeffentlich')) ?></p>
        <form method="post" style="margin-bottom:var(--abstand-l);">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="web_aktivieren">
            <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
            <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('gruppe.web_veroeffentlichen_btn')) ?></button>
        </form>
    <?php endif; ?>

    <p class="text-klein text-sekundaer">
        <?= Helpers::e(t('gruppe.web_stats', ['gesamt' => (int) ($webStats['gesamt'] ?? 0), 'freigegeben' => (int) ($webStats['freigegeben'] ?? 0)])) ?>
    </p>
    <?php if ((int) ($webStats['freigegeben'] ?? 0) < (int) ($webStats['gesamt'] ?? 0)): ?>
        <form method="post" style="margin-bottom:var(--abstand-m);">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="web_alle_freigeben">
            <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
            <button type="submit" class="btn btn--klein"><?= Helpers::e(t('gruppe.web_alle_freigeben')) ?></button>
        </form>
    <?php endif; ?>

    <hr style="margin:var(--abstand-m) 0; border:none; border-top:1px solid var(--farbe-linie);">
    <h4 style="margin-bottom:var(--abstand-m);"><?= Helpers::e(t('gruppe.web_einstellungen')) ?></h4>
    <form method="post">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="web_einstellungen">
        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">

        <div class="feld">
            <label for="web_titel"><?= Helpers::e(t('gruppe.web_titel_feld')) ?> <span class="text-sekundaer"><?= Helpers::e(t('gruppe.web_titel_hinweis')) ?></span></label>
            <input type="text" id="web_titel" name="web_titel"
                   value="<?= Helpers::e((string) ($gruppe['web_titel'] ?? '')) ?>" maxlength="200">
        </div>

        <div class="feld">
            <label for="web_einleitung"><?= Helpers::e(t('gruppe.web_einleitung')) ?></label>
            <textarea id="web_einleitung" name="web_einleitung" rows="3"><?= Helpers::e((string) ($gruppe['web_einleitung'] ?? '')) ?></textarea>
        </div>

        <div class="feld">
            <label><?= Helpers::e(t('gruppe.web_bilder_modus')) ?></label>
            <div class="checkliste">
                <label><input type="radio" name="web_bilder_modus" value="haupt"
                    <?= ($gruppe['web_bilder_modus'] ?? 'haupt') !== 'alle' ? 'checked' : '' ?>> <?= Helpers::e(t('gruppe.web_nur_hauptbild')) ?></label>
                <label><input type="radio" name="web_bilder_modus" value="alle"
                    <?= ($gruppe['web_bilder_modus'] ?? 'haupt') === 'alle' ? 'checked' : '' ?>> <?= Helpers::e(t('gruppe.web_alle_bilder')) ?></label>
            </div>
        </div>

        <?php if ($webFeldOptionen): ?>
        <div class="feld">
            <label><?= Helpers::e(t('gruppe.web_felder')) ?></label>
            <div class="checkliste">
                <?php foreach ($webFeldOptionen as $feld): ?>
                    <label>
                        <input type="checkbox" name="web_feld_<?= Helpers::e($feld['key']) ?>"
                               <?= in_array($feld['key'], $erlaubteFelder, true) ? 'checked' : '' ?>>
                        <?= Helpers::e(t($feld['label'])) ?>
                        <?php if (!empty($feld['sensibel'])): ?>
                            <span class="text-sekundaer" style="font-size:11px;"> <?= Helpers::e(t('gruppe.web_sensibel')) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="feldreihe">
            <div class="feld">
                <label for="web_passwort_neu"><?= Helpers::e(t('gruppe.web_passwort')) ?> <span class="text-sekundaer"><?= Helpers::e(t('gruppe.web_passwort_hinweis')) ?></span></label>
                <input type="password" id="web_passwort_neu" name="web_passwort_neu"
                       autocomplete="new-password"
                       placeholder="<?= !empty($gruppe['web_passwort_hash']) ? Helpers::e(t('gruppe.web_passwort_gesetzt')) : Helpers::e(t('gruppe.web_passwort_keines')) ?>">
            </div>
            <?php if (!empty($gruppe['web_passwort_hash'])): ?>
                <div class="feld" style="display:flex; align-items:flex-end; padding-bottom:var(--abstand-m);">
                    <label style="display:inline-flex; align-items:center; gap:6px; color:var(--farbe-text);">
                        <input type="checkbox" name="web_passwort_entfernen"> <?= Helpers::e(t('gruppe.web_passwort_entfernen')) ?>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="feldreihe">
            <div class="feld">
                <label for="web_ablauf_typ"><?= Helpers::e(t('gruppe.web_ablauf')) ?></label>
                <select id="web_ablauf_typ" name="web_ablauf_typ">
                    <option value="kein" <?= empty($gruppe['web_ablauf']) ? 'selected' : '' ?>><?= Helpers::e(t('gruppe.web_kein_ablauf')) ?></option>
                    <option value="7"><?= Helpers::e(t('gruppe.web_ablauf_7')) ?></option>
                    <option value="14"><?= Helpers::e(t('gruppe.web_ablauf_14')) ?></option>
                    <option value="30"><?= Helpers::e(t('gruppe.web_ablauf_30')) ?></option>
                    <option value="datum" <?= !empty($gruppe['web_ablauf']) ? 'selected' : '' ?>><?= Helpers::e(t('gruppe.web_ablauf_datum')) ?></option>
                </select>
            </div>
            <div class="feld">
                <label for="web_ablauf_datum"><?= Helpers::e(t('gruppe.web_ablauf_datum_feld')) ?></label>
                <input type="date" id="web_ablauf_datum" name="web_ablauf_datum"
                       value="<?= Helpers::e(substr((string) ($gruppe['web_ablauf'] ?? ''), 0, 10)) ?>">
            </div>
        </div>

        <div class="feld">
            <label for="web_einbetten_von"><?= Helpers::e(t('gruppe.web_einbetten')) ?> <span class="text-sekundaer"><?= Helpers::e(t('gruppe.web_einbetten_hinweis')) ?></span></label>
            <input type="text" id="web_einbetten_von" name="web_einbetten_von"
                   value="<?= Helpers::e((string) ($gruppe['web_einbetten_von'] ?? '')) ?>"
                   placeholder="z.B. example.com">
        </div>

        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('gruppe.web_einstellungen_speichern')) ?></button>
    </form>
</div>

<!-- ── Werk-Reihenfolge ───────────────────────────────────────────── -->
<?php if (count($werkeSortiert) > 1): ?>
<div class="karte" style="max-width:900px;">
    <h3><?= Helpers::e(t('gruppe.reihenfolge')) ?></h3>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('gruppe.reihenfolge_hinweis')) ?></p>
    <ul class="bild-liste" style="list-style:none; padding:0; margin:0 0 var(--abstand-m);">
        <?php $werkAnzahl = count($werkeSortiert); foreach ($werkeSortiert as $i => $w): $wid = (int) $w['id']; ?>
            <li class="bild-karte" data-werk-id="<?= $wid ?>">
                <div class="bild-karte__reihenfolge">
                    <form method="post">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="werk_nach_oben">
                        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                        <input type="hidden" name="werk_id" value="<?= $wid ?>">
                        <button type="submit" class="btn btn--klein" <?= $i === 0 ? 'disabled' : '' ?> aria-label="↑">↑</button>
                    </form>
                    <form method="post">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="werk_nach_unten">
                        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                        <input type="hidden" name="werk_id" value="<?= $wid ?>">
                        <button type="submit" class="btn btn--klein" <?= $i === $werkAnzahl - 1 ? 'disabled' : '' ?> aria-label="↓">↓</button>
                    </form>
                </div>
                <?php $bild = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 'm'); ?>
                <?php if ($bild): ?>
                    <div class="passepartout bild-karte__vorschau">
                        <img src="<?= Helpers::e($bild) ?>" alt="" loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="bild-karte__meta">
                    <?= Helpers::e($w['maler']) ?>
                    <?php if ($w['titel']): ?> · <em><?= Helpers::e($w['titel']) ?></em><?php endif; ?>
                    <?php if (!empty($w['web_freigabe'])): ?>
                        <span class="text-sekundaer text-klein"> · <?= Helpers::e(t('gruppe.web_freigabe_check')) ?></span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <form method="post" id="werk-sortierung-form" hidden>
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="werk_reihenfolge">
        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
        <?php foreach ($werkeSortiert as $w): ?>
            <input type="hidden" name="werk_reihenfolge[]" value="<?= (int) $w['id'] ?>">
        <?php endforeach; ?>
        <button type="submit" class="btn"><?= Helpers::e(t('gruppe.reihenfolge_speichern')) ?></button>
    </form>
</div>
<?php endif; ?>

<?php endif; // $istAdmin ?>

<!-- ── PDF-Dialog ─────────────────────────────────────────────────── -->
<?php
$pdfStandardFelder = ExportProfile::standardFelder();
$pdfAlleFelder     = Felder::alle();
$pdfGruppen        = array_unique(array_column($pdfAlleFelder, 'gruppe'));
?>
<dialog id="pdf-dialog"
        data-gruppe-id="<?= $gruppeId ?>"
        data-csrf="<?= Helpers::e(Helpers::csrfToken()) ?>"
        data-profil-name-frage="<?= Helpers::e(t('pdf.profil_name_frage')) ?>"
        style="width:min(700px,95vw); max-height:90vh; overflow-y:auto; padding:var(--abstand-l); border-radius:var(--radius); border:1px solid var(--farbe-linie);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--abstand-m);">
        <h2 style="margin:0;"><?= Helpers::e(t('pdf.dialog_titel')) ?></h2>
        <button type="button" class="btn btn--klein" onclick="this.closest('dialog').close()">✕</button>
    </div>

    <?php if ($pdfProfile): ?>
    <div class="feld" style="max-width:400px; margin-bottom:var(--abstand-m);">
        <label for="pdf-profil"><?= Helpers::e(t('pdf.profil_waehlen')) ?></label>
        <select id="pdf-profil">
            <option value=""><?= Helpers::e(t('pdf.profil_standard')) ?></option>
            <?php foreach ($pdfProfile as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                        data-felder="<?= Helpers::e((string) ($p['felder'] ?? '')) ?>"
                        data-bild="<?= (int) $p['bild'] ?>"
                        data-titelblock="<?= (int) $p['titelblock'] ?>"
                        data-leer="<?= (int) $p['leer_ausblenden'] ?>"
                        data-summe="<?= (int) $p['summe'] ?>"
                        data-layout="<?= Helpers::e((string) $p['layout']) ?>"
                ><?= Helpers::e($p['name']) ?><?= $p['ist_standard'] ? ' (' . Helpers::e(t('profil.standard')) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($istAdmin): ?>
    <p class="text-sekundaer text-klein" style="margin:0 0 var(--abstand-s);"><?= Helpers::e(t('pdf.felder_titel')) ?></p>
    <?php foreach ($pdfGruppen as $pdfGrpName): ?>
    <fieldset style="border:1px solid var(--farbe-linie); margin-bottom:var(--abstand-s); padding:var(--abstand-s) var(--abstand-m);">
        <legend style="font-size:var(--schrift-groesse-klein); font-weight:600; padding:0 var(--abstand-xs);"><?= Helpers::e(t('feldgruppe.' . $pdfGrpName)) ?></legend>
        <div class="checkliste">
        <?php foreach ($pdfAlleFelder as $pdfF): if ($pdfF['gruppe'] !== $pdfGrpName) continue; ?>
            <label style="font-size:var(--schrift-groesse-klein);">
                <input type="checkbox" name="felder[]" value="<?= Helpers::e($pdfF['key']) ?>"
                       <?= in_array($pdfF['key'], $pdfStandardFelder, true) ? 'checked' : '' ?>>
                <?= Helpers::e(t($pdfF['label'])) ?>
                <?php if ($pdfF['sensibel']): ?>
                    <span class="text-sekundaer"> (<?= Helpers::e(t('profil.sensibel_hinweis')) ?>)</span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        </div>
    </fieldset>
    <?php endforeach; ?>
    <?php endif; // $istAdmin – Felder ?>

    <div class="checkliste" style="margin:var(--abstand-m) 0;">
        <label><input type="checkbox" id="pdf-bild" checked> <?= Helpers::e(t('pdf.bild_zeigen')) ?></label>
        <label><input type="checkbox" id="pdf-titelblock" checked> <?= Helpers::e(t('pdf.titelblock_zeigen')) ?></label>
        <label><input type="checkbox" id="pdf-leer" checked> <?= Helpers::e(t('pdf.leer_ausblenden')) ?></label>
        <label><input type="checkbox" id="pdf-summe" checked> <?= Helpers::e(t('pdf.summe_zeigen')) ?></label>
    </div>

    <div class="feld" style="max-width:200px; margin-bottom:var(--abstand-m);">
        <label for="pdf-layout"><?= Helpers::e(t('pdf.layout')) ?></label>
        <select id="pdf-layout">
            <option value="einzelblatt"><?= Helpers::e(t('pdf.layout_einzelblatt')) ?></option>
            <option value="liste"><?= Helpers::e(t('pdf.layout_liste')) ?></option>
        </select>
    </div>

    <div style="display:flex; gap:var(--abstand-s); flex-wrap:wrap; align-items:center;">
        <a id="pdf-erstellen-link" href="#" target="_blank" rel="noopener" class="btn btn--primaer"><?= Helpers::e(t('pdf.erstellen')) ?></a>
        <?php if ($istAdmin): ?>
            <button type="button" id="pdf-als-profil" class="btn"><?= Helpers::e(t('pdf.als_profil_speichern')) ?></button>
            <button type="button" id="pdf-profil-update" class="btn" hidden><?= Helpers::e(t('pdf.profil_aktualisieren')) ?></button>
        <?php endif; ?>
    </div>
</dialog>
<script src="/assets/pdf-dialog.js"></script>

<?php if ($auswahlVerwerfen): ?>
    <div data-auswahl-loeschen="<?= Helpers::e(implode(' ', $auswahlVerwerfen)) ?>" hidden></div>
    <script src="/assets/auswahl.js"></script>
<?php endif; ?>
