<?php
/**
 * Filterleiste für Betrachter (Gruppenansicht und Web-Galerie). Zeigt nur die
 * angebotenen Filter; Werte stammen aus den Werken, die der Betrachter sehen darf.
 * Reines GET-Formular ohne JavaScript; die Auswahl steht in der Adresse.
 *
 * @var string[] $filterAngebot
 * @var ?array   $filter         aktuelle Auswahl (normalisiert) oder null
 * @var array    $filterWerte    je Listenfeld [['wert' => …, 'anzahl' => …], …]
 * @var int      $filterTreffer  Anzahl Werke mit Auswahl
 * @var int      $gesamtAnzahl   Anzahl Werke ohne Auswahl
 * @var string   $filterAktion   Ziel des Formulars
 * @var array    $filterVersteckt zusätzliche Parameter (name => wert), z. B. ['id' => 5]
 */
declare(strict_types=1);

use App\Felder;
use App\GruppenRegeln;
use App\Helpers;

$filter ??= null;
$listen   = array_values(array_intersect(GruppenRegeln::FILTER_LISTEN, $filterAngebot));
$bereiche = array_values(array_intersect(GruppenRegeln::FILTER_BEREICHE, $filterAngebot));
$suche    = in_array('titel', $filterAngebot, true);
$zuruecksetzen = $filterAktion . ($filterVersteckt ? '?' . http_build_query($filterVersteckt) : '');
?>
<details class="filterleiste" <?= $filter !== null ? 'open' : '' ?> style="margin:var(--abstand-m, 1rem) 0; border:1px solid var(--farbe-linie, #E2E0DC); padding:var(--abstand-s, .5rem) var(--abstand-m, 1rem);">
    <summary style="cursor:pointer;">
        <strong><?= Helpers::e(t('filter.titel')) ?></strong>
        <?php if ($filter !== null): ?>
            · <span class="filter-treffer"><?= Helpers::e(t('filter.treffer', ['n' => $filterTreffer, 'm' => $gesamtAnzahl])) ?></span>
        <?php endif; ?>
    </summary>
    <form method="get" action="<?= Helpers::e($filterAktion) ?>" style="margin-top:var(--abstand-s, .5rem);">
        <?php foreach ($filterVersteckt as $name => $wert): ?>
            <input type="hidden" name="<?= Helpers::e((string) $name) ?>" value="<?= Helpers::e((string) $wert) ?>">
        <?php endforeach; ?>
        <div style="display:flex; flex-wrap:wrap; gap:var(--abstand-m, 1rem);">
            <?php foreach ($listen as $f): $gewaehlt = $filter[$f] ?? []; $werte = $filterWerte[$f] ?? []; ?>
            <fieldset style="flex:1 1 180px; min-width:0; border:none; padding:0; margin:0;">
                <legend style="font-weight:600;"><?= Helpers::e(Felder::label($f)) ?></legend>
                <?php if ($werte): ?>
                <div style="max-height:180px; overflow-y:auto;">
                    <?php foreach ($werte as $w): ?>
                        <label style="display:block;"><input type="checkbox" name="f_<?= $f ?>[]" value="<?= Helpers::e($w['wert']) ?>" <?= in_array($w['wert'], $gewaehlt, true) ? 'checked' : '' ?>>
                            <?= Helpers::e($w['wert']) ?> <span class="text-sekundaer" style="opacity:.7;">(<?= (int) $w['anzahl'] ?>)</span></label>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p style="opacity:.7; margin:0;"><?= Helpers::e(t('filter.keine_werte')) ?></p>
                <?php endif; ?>
            </fieldset>
            <?php endforeach; ?>
            <?php foreach ($bereiche as $f): $schritt = $f === 'entstehungsjahr' ? '1' : '0.01'; ?>
            <fieldset style="flex:1 1 180px; min-width:0; border:none; padding:0; margin:0;">
                <legend style="font-weight:600;"><?= Helpers::e(Felder::label($f)) ?></legend>
                <label style="display:block;"><?= Helpers::e(t('filter.von')) ?>
                    <input type="number" name="f_<?= $f ?>_von" step="<?= $schritt ?>" value="<?= Helpers::e((string) ($filter[$f]['von'] ?? '')) ?>" style="max-width:9em;"></label>
                <label style="display:block;"><?= Helpers::e(t('filter.bis')) ?>
                    <input type="number" name="f_<?= $f ?>_bis" step="<?= $schritt ?>" value="<?= Helpers::e((string) ($filter[$f]['bis'] ?? '')) ?>" style="max-width:9em;"></label>
            </fieldset>
            <?php endforeach; ?>
            <?php if ($suche): ?>
            <fieldset style="flex:1 1 180px; min-width:0; border:none; padding:0; margin:0;">
                <legend style="font-weight:600;"><?= Helpers::e(t('filter.titel_enthaelt')) ?></legend>
                <input type="text" name="f_titel" maxlength="100" value="<?= Helpers::e((string) ($filter['titel_enthaelt'] ?? '')) ?>" style="width:100%;">
            </fieldset>
            <?php endif; ?>
        </div>
        <div style="display:flex; gap:var(--abstand-s, .5rem); flex-wrap:wrap; align-items:center; margin-top:var(--abstand-s, .5rem);">
            <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('filter.anwenden')) ?></button>
            <?php if ($filter !== null): ?>
                <a href="<?= Helpers::e($zuruecksetzen) ?>" class="btn"><?= Helpers::e(t('filter.zuruecksetzen')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</details>
