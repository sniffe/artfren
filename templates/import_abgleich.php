<?php
/** @var array $abgleich */
/** @var string $tmp_datei */
/** @var string $trennzeichen */
/** @var array<int,string> $mapping */
/** @var int $gesamtzeilen */
declare(strict_types=1);

use App\Helpers;

$zeigeListe = static function (array $eintraege, int $max = 15): void {
    foreach (array_slice($eintraege, 0, $max) as $eintrag) {
        $d = $eintrag['daten'];
        echo '<li>Zeile ' . (int) $eintrag['zeilennummer'] . ': ' . Helpers::e(trim(($d['ort'] ?? '') . ' – ' . ($d['maler'] ?? '') . ' – ' . ($d['titel'] ?? ''), ' –')) . '</li>';
    }
    if (count($eintraege) > $max) {
        echo '<li class="text-sekundaer">… und ' . (count($eintraege) - $max) . ' weitere</li>';
    }
};
?>
<h2>CSV-Import – Abgleich</h2>
<p class="text-sekundaer"><?= $gesamtzeilen ?> Datenzeilen eingelesen. Abgleich anhand von Ort + Maler + Titel gegen den vorhandenen Bestand:</p>

<div class="feldreihe">
    <div class="karte" style="flex:1;"><strong><?= count($abgleich['neu']) ?></strong><div class="text-klein text-sekundaer">neu</div></div>
    <div class="karte" style="flex:1;"><strong><?= count($abgleich['geaendert']) ?></strong><div class="text-klein text-sekundaer">aktualisiert</div></div>
    <div class="karte" style="flex:1;"><strong><?= $abgleich['unveraendert'] ?></strong><div class="text-klein text-sekundaer">unverändert</div></div>
    <div class="karte" style="flex:1;"><strong><?= count($abgleich['fehlerBild']) ?></strong><div class="text-klein text-sekundaer">fehlendes Bild</div></div>
</div>

<div class="feldreihe mt-l">
    <div class="karte" style="flex:1;">
        <h3>Neue Werke</h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['neu']); ?></ul>
    </div>
    <div class="karte" style="flex:1;">
        <h3>Aktualisierte Werke</h3>
        <ul class="text-klein"><?php $zeigeListe($abgleich['geaendert']); ?></ul>
    </div>
</div>

<?php if ($abgleich['fehlerBild']): ?>
<div class="karte mt-m">
    <h3>Fehlerhafte Zeilen – referenziertes Bild fehlt im Bilder-Ordner</h3>
    <p class="text-klein text-sekundaer">Diese Zeilen werden trotzdem importiert, aber ohne Bild-Verknüpfung. Bild später nachreichen und erneut importieren.</p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['fehlerBild'], 50); ?></ul>
</div>
<?php endif; ?>

<?php if ($abgleich['duplikate']): ?>
<div class="karte mt-m">
    <h3>Achtung: doppelte Ort/Maler/Titel-Kombination in der Datei</h3>
    <p class="text-klein text-sekundaer">Bei gleichen Werten wird nur die letzte Zeile übernommen. Bitte bei Bedarf manuell prüfen.</p>
    <ul class="text-klein"><?php $zeigeListe($abgleich['duplikate'], 50); ?></ul>
</div>
<?php endif; ?>

<form method="post" action="/import.php" class="mt-l">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="uebernehmen">
    <input type="hidden" name="tmp_datei" value="<?= Helpers::e($tmp_datei) ?>">
    <input type="hidden" name="trennzeichen" value="<?= Helpers::e($trennzeichen) ?>">
    <?php foreach ($mapping as $index => $ziel): ?>
        <input type="hidden" name="mapping[<?= (int) $index ?>]" value="<?= Helpers::e($ziel) ?>">
    <?php endforeach; ?>
    <button type="submit" class="btn btn--primaer">Jetzt importieren</button>
    <a href="/import.php" class="btn">Abbrechen</a>
</form>
