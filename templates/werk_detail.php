<?php
/** @var array $werk */
/** @var array $bilder */
declare(strict_types=1);

use App\Felder;
use App\Helpers;

$hauptbild = $bilder[0] ?? null;
$bildUrl = $hauptbild ? Helpers::bildUrl((int) $hauptbild['id'], $hauptbild['dateiname'], 'g') : null;
$originalUrl = $hauptbild ? Helpers::bildUrl((int) $hauptbild['id'], $hauptbild['dateiname'], 'o') : null;
$istAdmin = \App\Auth::isAdmin($aktuellerBenutzer);

// Hilfsfunktion: formatierten Wert eines Feldes ausgeben (escaped).
$zeigeWert = static function (array $feld, array $werk) use ($istAdmin): string {
    $wert = $werk[$feld['key']] ?? null;
    return match ($feld['typ']) {
        'geld'     => Helpers::formatGeld($wert !== null ? (float) $wert : null),
        'langtext' => nl2br(Helpers::e((string) $wert)),
        'auswahl'  => $feld['key'] === 'status_farbe'
            ? '<span class="status-punkt status-punkt--' . Helpers::e((string) $wert) . '"></span> '
              . Helpers::e(Helpers::statusLabel((string) $wert))
            : Helpers::e((string) $wert),
        'janein'   => 'ja',
        default    => Helpers::e((string) $wert),
    };
};

// Felder, die in der Detail-Ansicht sichtbar sind.
$sichtbareFelder = array_filter(
    Felder::alle(),
    static function (array $feld) use ($werk, $istAdmin): bool {
        if (!$feld['in_detail']) {
            return false;
        }
        // beschreibung_intern nur fuer Admins
        if ($feld['key'] === 'beschreibung_intern' && !$istAdmin) {
            return false;
        }
        $wert = $werk[$feld['key']] ?? null;
        // janein-Felder nur anzeigen, wenn Wert 1 (freigegeben)
        if ($feld['typ'] === 'janein') {
            return (int) $wert === 1;
        }
        return $wert !== null && $wert !== '';
    }
);
?>
<div class="toolbar">
    <p class="text-klein" style="margin:0;"><a href="/" data-zurueck>← zurück</a></p>
    <?php if ($istAdmin): ?>
        <div class="toolbar-aktionen">
            <a href="/werk_bearbeiten.php?id=<?= (int) $werk['id'] ?>&amp;zurueck=<?= rawurlencode('/werk.php?id=' . (int) $werk['id']) ?>" class="btn btn--primaer">Bearbeiten</a>
            <form method="post" action="/papierkorb.php"
                  data-bestaetigen="Werk „<?= Helpers::e($werk['titel'] ?? 'Unbenannt') ?>" in den Papierkorb verschieben?">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="bulk_papierkorb">
                <input type="hidden" name="werk_ids[]" value="<?= (int) $werk['id'] ?>">
                <button type="submit" class="btn btn--gefahr"><?= Helpers::icon('papierkorb') ?> In den Papierkorb</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<div class="vitrine-detail">
    <div class="passepartout">
        <?php if ($bildUrl): ?>
            <img src="<?= Helpers::e($bildUrl) ?>" alt="<?= Helpers::e($werk['titel']) ?>">
        <?php elseif ($hauptbild): ?>
            <span class="passepartout--leer">Bilddatei „<?= Helpers::e($hauptbild['dateiname']) ?>" fehlt noch</span>
        <?php else: ?>
            <span class="passepartout--leer">Kein Bild hinterlegt</span>
        <?php endif; ?>
    </div>
    <div class="werk-etikett">
        <div class="maler"><?= Helpers::e($werk['maler']) ?></div>
        <div class="titel"><?= Helpers::e($werk['titel']) ?></div>
        <div class="meta"><?= Helpers::e(Helpers::werkMeta($werk['technik'] ?? null, $werk['entstehungsjahr'] ?? null)) ?></div>

        <?php
        $aktuelleGruppe = null;
        $dlOffen = false;
        foreach ($sichtbareFelder as $feld):
            if ($aktuelleGruppe !== $feld['gruppe']):
                if ($dlOffen): ?></dl><?php $dlOffen = false; endif;
                $aktuelleGruppe = $feld['gruppe'];
        ?>
        <h4 class="text-sekundaer text-klein mt-m"><?= Helpers::e($aktuelleGruppe) ?></h4>
        <dl>
        <?php $dlOffen = true; ?>
        <?php endif; ?>
            <dt><?= Helpers::e($feld['label']) ?></dt>
            <dd>
                <?= $zeigeWert($feld, $werk) ?>
                <?php if ($feld['key'] === 'beschreibung_intern'): ?>
                    <span class="text-sekundaer text-klein"> – intern</span>
                <?php endif; ?>
            </dd>
        <?php endforeach; ?>
        <?php if ($dlOffen): ?></dl><?php endif; ?>

        <?php if ($originalUrl): ?>
            <p class="mt-m"><a href="<?= Helpers::e($originalUrl) ?>" target="_blank" rel="noopener" class="btn btn--klein">Originalbild öffnen</a></p>
        <?php endif; ?>
    </div>
</div>

<?php if (count($bilder) > 1): ?>
<div class="galerie-streifen mt-m">
    <?php foreach (array_slice($bilder, 1) as $bild): ?>
        <?php
        $bThumbUrl = Helpers::bildUrl((int) $bild['id'], $bild['dateiname'], 't');
        $bOrigUrl  = Helpers::bildUrl((int) $bild['id'], $bild['dateiname'], 'o');
        ?>
        <a href="<?= Helpers::e($bOrigUrl ?? '#') ?>" target="_blank" rel="noopener" class="galerie-streifen__thumb passepartout"
           title="<?= Helpers::e((string) ($bild['beschriftung'] ?? $bild['dateiname'])) ?>">
            <?php if ($bThumbUrl): ?>
                <img src="<?= Helpers::e($bThumbUrl) ?>" alt="<?= Helpers::e((string) ($bild['beschriftung'] ?? '')) ?>">
            <?php else: ?>
                <span class="passepartout--leer">fehlt</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
