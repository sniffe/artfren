<?php
/** @var array $gruppe */
/** @var array $werke */
/** @var bool $istAdmin */
/** @var array $freigegebenFuer */
/** @var string[] $auswahlVerwerfen */
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
        <a href="/export_gruppe_zip.php?id=<?= $gruppeId ?>" class="btn">Export ZIP</a>
        <a href="/export_gruppe_pdf.php?id=<?= $gruppeId ?>" class="btn">Export PDF</a>
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

<?php if ($auswahlVerwerfen): ?>
    <div data-auswahl-loeschen="<?= Helpers::e(implode(' ', $auswahlVerwerfen)) ?>" hidden></div>
    <script src="/assets/auswahl.js"></script>
<?php endif; ?>
