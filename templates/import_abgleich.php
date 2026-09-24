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
        echo '<li>Zeile ' . (int) $eintrag['zeilennummer'] . ': ' . Helpers::e($text);
        if ($mitUnterschieden && !empty($eintrag['unterschiede'])) {
            $felder = array_map(static fn($f) => TabellenImport::ZIELFELDER[$f] ?? $f, $eintrag['unterschiede']);
            echo ' <span class="text-sekundaer">(' . Helpers::e(implode(', ', $felder)) . ')</span>';
        }
        if (isset($d['bild_dateiname']) && $d['bild_dateiname'] !== null && !$mitUnterschieden) {
            echo ' <span class="text-sekundaer">– ' . Helpers::e($d['bild_dateiname']) . '</span>';
        }
        echo '</li>';
    }
    if (count($eintraege) > $max) {
        echo '<li class="text-sekundaer">… und ' . (count($eintraege) - $max) . ' weitere</li>';
    }
};

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<h2>Abgleich mit dem Bestand</h2>
<p class="text-sekundaer">
    <?= $gesamtzeilen ?> Werke eingelesen<?= $uebersprungen ? ", {$uebersprungen} Zeilen ohne Ort/Maler/Titel übersprungen (z. B. Summenzeilen)" : '' ?>.
    <?php if ($aliasErsetzt): ?><?= $aliasErsetzt ?> Ort-Schreibweisen wurden anhand der zusammengeführten Orte vereinheitlicht.<?php endif; ?>
</p>

<div class="kennzahlen">
    <div class="karte"><strong><?= count($abgleich['neu']) ?></strong><span class="text-klein text-sekundaer">neu</span></div>
    <div class="karte"><strong><?= count($abgleich['geaendert']) ?></strong><span class="text-klein text-sekundaer">werden aktualisiert</span></div>
    <div class="karte"><strong><?= $abgleich['unveraendert'] ?></strong><span class="text-klein text-sekundaer">unverändert</span></div>
    <div class="karte"><strong><?= count($abgleich['fehlerBild']) ?></strong><span class="text-klein text-sekundaer">Bilddatei fehlt noch</span></div>
</div>

<?php if ($nichtZugeordnet): ?>
    <div class="flash flash--hinweis mt-m">Nicht zugeordnete Felder bleiben bei bestehenden Werken unverändert: <?= Helpers::e(implode(', ', array_map(static fn($f) => TabellenImport::ZIELFELDER[$f], $nichtZugeordnet))) ?>.</div>
<?php endif; ?>

<div class="feldreihe mt-l">
    <div class="karte" style="flex:1;min-width:300px;">
        <h3>Neue Werke</h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['neu']); ?></ul>
    </div>
    <div class="karte" style="flex:1;min-width:300px;">
        <h3>Änderungen an bestehenden Werken</h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['geaendert'], 25, true); ?></ul>
    </div>
</div>

<?php if ($abgleich['fehlerBild']): ?>
<div class="karte mt-m">
    <h3>Bilddatei fehlt noch</h3>
    <p class="text-klein text-sekundaer">Diese Werke werden importiert und mit dem Dateinamen verknüpft. Sobald die Bilder unter „Bilder hochladen“ ergänzt werden, erscheinen sie automatisch – ein erneuter Import ist nicht nötig.</p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['fehlerBild'], 50); ?></ul>
</div>
<?php endif; ?>

<?php if ($ungueltigeDateinamen): ?>
<div class="karte mt-m">
    <h3>Ungültige Bild-Dateinamen</h3>
    <p class="text-klein text-sekundaer">Erlaubt sind nur Namen ohne Ordnerangabe mit Endung <?= Helpers::e(implode(', ', BILD_ENDUNGEN)) ?>. Diese Werke werden ohne Bild importiert.</p>
    <ul class="text-klein"><?php $zeigeListe($ungueltigeDateinamen, 50); ?></ul>
</div>
<?php endif; ?>

<?php if ($abgleich['duplikate']): ?>
<div class="karte mt-m">
    <h3>Mehrfach vorkommende Ort/Maler/Titel-Kombinationen</h3>
    <p class="text-klein text-sekundaer">Diese Zeilen werden als eigene Werke geführt und beim erneuten Import der Reihe nach zugeordnet (1. Vorkommen ↔ 1. Werk usw.). Ändert sich die Reihenfolge in der Tabelle, können Werte vertauscht werden – eindeutige Titel (z. B. „o.T. 1“, „o.T. 2“) vermeiden das.</p>
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
    <button type="submit" class="btn btn--primaer">Jetzt importieren</button>
    <a href="/import.php" class="btn">Abbrechen</a>
</form>
