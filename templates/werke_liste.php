<?php
/** @var array $werke */
/** @var array $orte */
/** @var string $q */
/** @var string $ortFilter */
/** @var int $seite */
/** @var int $seitenAnzahl */
/** @var int $gesamtAnzahl */
/** @var array|null $gruppe */
/** @var array $aktuelleMitglieder */
declare(strict_types=1);

use App\Helpers;

$gruppeId = $gruppe['id'] ?? null;
$auswahlSchluessel = $gruppeId !== null ? 'auswahl_gruppe_' . $gruppeId : 'auswahl_neu';
$auswahlStart = $gruppeId !== null ? json_encode(array_map('strval', $aktuelleMitglieder)) : 'null';

function seiten_url(array $ueberschreiben = []): string
{
    $params = array_merge($_GET, $ueberschreiben);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return '/werke.php?' . http_build_query($params);
}
?>
<div data-auswahl-schluessel="<?= Helpers::e($auswahlSchluessel) ?>" data-auswahl-start='<?= $auswahlStart ?>'>

<?php if ($gruppe): ?>
    <h2>Werke für Gruppe „<?= Helpers::e($gruppe['name']) ?>“ auswählen</h2>
    <p class="text-sekundaer">Aktuelle Mitglieder sind bereits angehakt. Auswahl anpassen und unten speichern.</p>
<?php else: ?>
    <h2>Werke</h2>
<?php endif; ?>

<form method="get" action="/werke.php" class="werkstatt-leiste">
    <?php if ($gruppeId !== null): ?><input type="hidden" name="gruppe_id" value="<?= (int) $gruppeId ?>"><?php endif; ?>
    <div class="feld">
        <label for="q">Suche (Maler, Titel, Ort)</label>
        <input type="search" id="q" name="q" value="<?= Helpers::e($q) ?>" placeholder="z. B. Zens, Madeira, Wohnung 17A …">
    </div>
    <div class="feld">
        <label for="ort">Ort</label>
        <select id="ort" name="ort">
            <option value="">alle Orte</option>
            <?php foreach ($orte as $o): ?>
                <option value="<?= Helpers::e($o['ort']) ?>" <?= $ortFilter === $o['ort'] ? 'selected' : '' ?>><?= Helpers::e($o['ort']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn">Filtern</button>
    <?php if ($q !== '' || $ortFilter !== ''): ?>
        <a href="<?= Helpers::e(seiten_url(['q' => null, 'ort' => null])) ?>" class="btn">Filter zurücksetzen</a>
    <?php endif; ?>
</form>

<p class="text-klein text-sekundaer"><?= $gesamtAnzahl ?> Werke gefunden · Seite <?= $seite ?> von <?= $seitenAnzahl ?></p>

<table class="werkliste">
    <thead>
        <tr>
            <th></th>
            <th>Bild</th>
            <th>Ort</th>
            <th>Maler</th>
            <th>Titel</th>
            <th>Format</th>
            <th>Technik</th>
            <th class="num">Jahr</th>
            <th class="num">Wert</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $w): $thumb = Helpers::thumbUrl($w['bild_dateiname']); ?>
        <tr>
            <td data-label="Auswahl"><input type="checkbox" data-werk-id="<?= (int) $w['id'] ?>"></td>
            <td data-label="Bild">
                <?php if ($thumb): ?>
                    <img class="thumb" src="<?= Helpers::e($thumb) ?>" alt="">
                <?php else: ?>
                    <span class="platzhalter-thumb"></span>
                <?php endif; ?>
            </td>
            <td data-label="Ort"><?= Helpers::e($w['ort']) ?></td>
            <td data-label="Maler"><?= Helpers::e($w['maler']) ?></td>
            <td data-label="Titel"><?= Helpers::e($w['titel']) ?></td>
            <td data-label="Format"><?= Helpers::e($w['format']) ?></td>
            <td data-label="Technik"><?= Helpers::e($w['technik']) ?></td>
            <td data-label="Jahr" class="num"><?= Helpers::e((string) $w['entstehungsjahr']) ?></td>
            <td data-label="Wert" class="num"><?= Helpers::formatGeld($w['wert'] !== null ? (float) $w['wert'] : null) ?></td>
            <td data-label="">
                <?php if ($w['status_farbe']): ?><span class="status-punkt status-punkt--<?= Helpers::e($w['status_farbe']) ?>" title="<?= Helpers::e($w['status_farbe']) ?>"></span><?php endif; ?>
                <a href="/werk.php?id=<?= (int) $w['id'] ?>" class="text-klein">ansehen</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$werke): ?>
        <tr><td colspan="10" class="text-sekundaer">Keine Werke gefunden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="seiten-nav">
    <?php if ($seite > 1): ?><a href="<?= Helpers::e(seiten_url(['seite' => $seite - 1])) ?>" class="btn btn--klein">← zurück</a><?php endif; ?>
    <span>Seite <?= $seite ?> / <?= $seitenAnzahl ?></span>
    <?php if ($seite < $seitenAnzahl): ?><a href="<?= Helpers::e(seiten_url(['seite' => $seite + 1])) ?>" class="btn btn--klein">weiter →</a><?php endif; ?>
</div>

<div class="auswahl-leiste">
    <span><strong data-auswahl-zaehler>0</strong> Werke ausgewählt</span>
    <div class="toolbar-aktionen">
        <button type="button" class="btn" data-auswahl-leeren>Auswahl leeren</button>
        <?php if ($gruppe): ?>
            <form method="post" action="/gruppe.php" data-auswahl-formular style="display:inline;">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="mitglieder_speichern">
                <input type="hidden" name="gruppe_id" value="<?= (int) $gruppe['id'] ?>">
                <button type="submit" class="btn btn--primaer">Auswahl als Mitglieder speichern</button>
            </form>
            <a href="/gruppe.php?id=<?= (int) $gruppe['id'] ?>" class="btn">Abbrechen</a>
        <?php else: ?>
            <a href="/gruppe_neu.php" class="btn btn--primaer">Weiter: Gruppe anlegen</a>
        <?php endif; ?>
    </div>
</div>

</div>
<script src="/assets/auswahl.js"></script>
