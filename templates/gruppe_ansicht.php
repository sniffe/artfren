<?php
/** @var array $gruppe */
/** @var array $werke */
/** @var bool $istAdmin */
/** @var array $freigegebenFuer */
/** @var string[] $auswahlVerwerfen */
/** @var int $bilderAnzahl */
/** @var array[] $werkeSortiert */
/** @var array $webStats */
/** @var array[] $webFeldOptionen */
/** @var string[] $erlaubteFelder */
/** @var string $baseUrl */
declare(strict_types=1);

use App\Helpers;

$gruppeId = (int) $gruppe['id'];
?>
<div class="toolbar">
    <div>
        <p class="text-klein text-sekundaer"><a href="/gruppen.php">← Gruppen</a></p>
        <h2 class="gruppenname"><?= Helpers::e($gruppe['name']) ?></h2>
        <p class="text-sekundaer text-klein">
            <?= count($werke) ?> Werke · erstellt am <?= Helpers::e(Helpers::formatDatum($gruppe['erstellt_am'], 'd.m.Y')) ?>
            <?php if ($istAdmin): ?>
                · sichtbar für:
                <?php if ($freigegebenFuer): ?>
                    <?php foreach ($freigegebenFuer as $i => $b): ?><?= $i > 0 ? ', ' : '' ?><a href="/benutzer_bearbeiten.php?id=<?= (int) $b['id'] ?>"><?= Helpers::e($b['echter_name'] ?: $b['benutzername']) ?></a><?php endforeach; ?>
                <?php else: ?>
                    nur Admins
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="toolbar-aktionen">
        <a href="/export_gruppe.php?id=<?= $gruppeId ?>&amp;format=xlsx" class="btn" title="Tabelle mit allen Feldern und Dateinamen">Excel</a>
        <?php if ($bilderAnzahl > 0): ?>
            <a href="/export_gruppe.php?id=<?= $gruppeId ?>&amp;format=bilder" class="btn" title="Bilddateien der Gruppe als ZIP">Bilder-ZIP (<?= $bilderAnzahl ?>)</a>
        <?php endif; ?>
        <a href="/export_gruppe_pdf.php?id=<?= $gruppeId ?>" class="btn">PDF</a>
        <?php if ($istAdmin): ?>
            <a href="/werke.php?gruppe_id=<?= $gruppeId ?>" class="btn btn--primaer">Werke hinzufügen/entfernen</a>
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
                    <span class="passepartout--leer">Kein Bild</span>
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
        <p class="text-sekundaer">Diese Gruppe enthält noch keine Werke.</p>
    <?php endif; ?>
</div>

<?php if ($istAdmin): ?>

<!-- ── Web-Veröffentlichung ───────────────────────────────────────── -->
<div class="karte mt-l" style="max-width:900px;">
    <h3>Im Web veröffentlichen</h3>

    <?php if ($gruppe['web_aktiv']): ?>
        <?php $galerieUrl = $baseUrl . '/w/' . $gruppe['web_token']; ?>
        <p>
            <span class="badge badge--admin">Veröffentlicht</span>
            <?php if (!empty($gruppe['web_ablauf'])): ?>
                · läuft ab <?= Helpers::e(Helpers::formatDatum($gruppe['web_ablauf'], 'd.m.Y')) ?>
            <?php endif; ?>
            <?php if (!empty($gruppe['web_passwort_hash'])): ?> · passwortgeschützt<?php endif; ?>
            <?php if (!empty($gruppe['web_aufrufe'])): ?> · <?= (int) $gruppe['web_aufrufe'] ?> Aufrufe<?php endif; ?>
        </p>
        <div style="display:flex; gap:var(--abstand-s); align-items:center; flex-wrap:wrap; margin-bottom:var(--abstand-m);">
            <code id="web-galerie-url" style="flex:1; min-width:200px; overflow-wrap:anywhere;"><?= Helpers::e($galerieUrl) ?></code>
            <button type="button" class="btn btn--klein" data-kopieren="web-galerie-url">Kopieren</button>
            <a href="<?= Helpers::e($galerieUrl) ?>" target="_blank" rel="noopener" class="btn btn--klein">↗ Öffnen</a>
        </div>
        <div style="display:flex; gap:var(--abstand-s); flex-wrap:wrap; margin-bottom:var(--abstand-l);">
            <form method="post">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="web_deaktivieren">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--gefahr btn--klein"
                        data-bestaetigen="Galerie wirklich zurückziehen? Der Link wird inaktiv.">Zurückziehen</button>
            </form>
            <form method="post">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="web_link_erneuern">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--klein"
                        data-bestaetigen="Link wirklich erneuern? Der alte Link wird sofort ungültig!">Link erneuern</button>
            </form>
        </div>
    <?php else: ?>
        <p class="text-sekundaer text-klein">Galerie ist nicht öffentlich zugänglich.</p>
        <form method="post" style="margin-bottom:var(--abstand-l);">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="web_aktivieren">
            <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
            <button type="submit" class="btn btn--primaer">Galerie veröffentlichen</button>
        </form>
    <?php endif; ?>

    <p class="text-klein text-sekundaer">
        <?= (int) ($webStats['gesamt'] ?? 0) ?> Werke in der Gruppe,
        davon <?= (int) ($webStats['freigegeben'] ?? 0) ?> für Web freigegeben.
    </p>
    <?php if ((int) ($webStats['freigegeben'] ?? 0) < (int) ($webStats['gesamt'] ?? 0)): ?>
        <form method="post" style="margin-bottom:var(--abstand-m);">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="web_alle_freigeben">
            <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
            <button type="submit" class="btn btn--klein">Alle Werke für Web freigeben</button>
        </form>
    <?php endif; ?>

    <hr style="margin:var(--abstand-m) 0; border:none; border-top:1px solid var(--farbe-linie);">
    <h4 style="margin-bottom:var(--abstand-m);">Galerie-Einstellungen</h4>
    <form method="post">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="web_einstellungen">
        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">

        <div class="feld">
            <label for="web_titel">Titel <span class="text-sekundaer">(leer = Gruppenname)</span></label>
            <input type="text" id="web_titel" name="web_titel"
                   value="<?= Helpers::e((string) ($gruppe['web_titel'] ?? '')) ?>" maxlength="200">
        </div>

        <div class="feld">
            <label for="web_einleitung">Einleitungstext</label>
            <textarea id="web_einleitung" name="web_einleitung" rows="3"><?= Helpers::e((string) ($gruppe['web_einleitung'] ?? '')) ?></textarea>
        </div>

        <div class="feld">
            <label>Bilder anzeigen</label>
            <div class="checkliste">
                <label><input type="radio" name="web_bilder_modus" value="haupt"
                    <?= ($gruppe['web_bilder_modus'] ?? 'haupt') !== 'alle' ? 'checked' : '' ?>> Nur Hauptbild</label>
                <label><input type="radio" name="web_bilder_modus" value="alle"
                    <?= ($gruppe['web_bilder_modus'] ?? 'haupt') === 'alle' ? 'checked' : '' ?>> Alle Bilder (Galerie-Streifen)</label>
            </div>
        </div>

        <?php if ($webFeldOptionen): ?>
        <div class="feld">
            <label>Sichtbare Felder in der Galerie</label>
            <div class="checkliste">
                <?php foreach ($webFeldOptionen as $feld): ?>
                    <label>
                        <input type="checkbox" name="web_feld_<?= Helpers::e($feld['key']) ?>"
                               <?= in_array($feld['key'], $erlaubteFelder, true) ? 'checked' : '' ?>>
                        <?= Helpers::e($feld['label']) ?>
                        <?php if (!empty($feld['sensibel'])): ?>
                            <span class="text-sekundaer" style="font-size:11px;"> (sensibel)</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="feldreihe">
            <div class="feld">
                <label for="web_passwort_neu">Passwort <span class="text-sekundaer">(leer = unverändert)</span></label>
                <input type="password" id="web_passwort_neu" name="web_passwort_neu"
                       autocomplete="new-password"
                       placeholder="<?= !empty($gruppe['web_passwort_hash']) ? '●●●●●●●● (gesetzt)' : 'Kein Passwort' ?>">
            </div>
            <?php if (!empty($gruppe['web_passwort_hash'])): ?>
                <div class="feld" style="display:flex; align-items:flex-end; padding-bottom:var(--abstand-m);">
                    <label style="display:inline-flex; align-items:center; gap:6px; color:var(--farbe-text);">
                        <input type="checkbox" name="web_passwort_entfernen"> Passwort entfernen
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="feldreihe">
            <div class="feld">
                <label for="web_ablauf_typ">Link-Ablauf</label>
                <select id="web_ablauf_typ" name="web_ablauf_typ">
                    <option value="kein" <?= empty($gruppe['web_ablauf']) ? 'selected' : '' ?>>Kein Ablauf</option>
                    <option value="7">7 Tage ab jetzt</option>
                    <option value="14">14 Tage ab jetzt</option>
                    <option value="30">30 Tage ab jetzt</option>
                    <option value="datum" <?= !empty($gruppe['web_ablauf']) ? 'selected' : '' ?>>Bestimmtes Datum …</option>
                </select>
            </div>
            <div class="feld">
                <label for="web_ablauf_datum">Ablaufdatum</label>
                <input type="date" id="web_ablauf_datum" name="web_ablauf_datum"
                       value="<?= Helpers::e(substr((string) ($gruppe['web_ablauf'] ?? ''), 0, 10)) ?>">
            </div>
        </div>

        <div class="feld">
            <label for="web_einbetten_von">Einbetten erlaubt von <span class="text-sekundaer">(Domain, leer = nein)</span></label>
            <input type="text" id="web_einbetten_von" name="web_einbetten_von"
                   value="<?= Helpers::e((string) ($gruppe['web_einbetten_von'] ?? '')) ?>"
                   placeholder="z.B. example.com">
        </div>

        <button type="submit" class="btn btn--primaer">Einstellungen speichern</button>
    </form>
</div>

<!-- ── Werk-Reihenfolge ───────────────────────────────────────────── -->
<?php if (count($werkeSortiert) > 1): ?>
<div class="karte" style="max-width:900px;">
    <h3>Reihenfolge in der Gruppe</h3>
    <p class="text-klein text-sekundaer">Gilt für die öffentliche Galerie und den PDF-Export.</p>
    <ul class="bild-liste" style="list-style:none; padding:0; margin:0 0 var(--abstand-m);">
        <?php $werkAnzahl = count($werkeSortiert); foreach ($werkeSortiert as $i => $w): $wid = (int) $w['id']; ?>
            <li class="bild-karte" data-werk-id="<?= $wid ?>">
                <div class="bild-karte__reihenfolge">
                    <form method="post">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="werk_nach_oben">
                        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                        <input type="hidden" name="werk_id" value="<?= $wid ?>">
                        <button type="submit" class="btn btn--klein" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Nach oben">↑</button>
                    </form>
                    <form method="post">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="werk_nach_unten">
                        <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                        <input type="hidden" name="werk_id" value="<?= $wid ?>">
                        <button type="submit" class="btn btn--klein" <?= $i === $werkAnzahl - 1 ? 'disabled' : '' ?> aria-label="Nach unten">↓</button>
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
                        <span class="text-sekundaer text-klein"> · Web ✓</span>
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
        <button type="submit" class="btn">Reihenfolge speichern</button>
    </form>
</div>
<?php endif; ?>

<?php endif; // $istAdmin ?>

<?php if ($auswahlVerwerfen): ?>
    <div data-auswahl-loeschen="<?= Helpers::e(implode(' ', $auswahlVerwerfen)) ?>" hidden></div>
    <script src="/assets/auswahl.js"></script>
<?php endif; ?>
