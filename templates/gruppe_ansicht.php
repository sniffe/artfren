<?php
/** @var array $gruppe */
/** @var array $werke */
/** @var bool $istAdmin */
/** @var bool $geradeErstellt */
/** @var bool $geradeGespeichert */
declare(strict_types=1);

use App\Helpers;
?>
<div class="toolbar">
    <div>
        <p class="text-klein text-sekundaer"><a href="/gruppen.php">← Gruppen</a></p>
        <h2><?= Helpers::e($gruppe['name']) ?></h2>
        <p class="text-sekundaer text-klein"><?= count($werke) ?> Werke · erstellt am <?= Helpers::e(Helpers::formatDatum($gruppe['erstellt_am'], 'd.m.Y')) ?></p>
    </div>
    <div class="toolbar-aktionen">
        <a href="/export_gruppe_zip.php?id=<?= (int) $gruppe['id'] ?>" class="btn">Export ZIP</a>
        <a href="/export_gruppe_pdf.php?id=<?= (int) $gruppe['id'] ?>" class="btn">Export PDF</a>
        <?php if ($istAdmin): ?>
            <a href="/werke.php?gruppe_id=<?= (int) $gruppe['id'] ?>" class="btn btn--primaer">Werke hinzufügen/entfernen</a>
        <?php endif; ?>
    </div>
</div>

<div class="vitrine-karten">
    <?php foreach ($werke as $w): $thumb = Helpers::bildUrl($w['bild_dateiname']); ?>
        <a class="vitrine-karte" href="/werk.php?id=<?= (int) $w['id'] ?>">
            <div class="passepartout">
                <?php if ($thumb): ?>
                    <img src="<?= Helpers::e($thumb) ?>" alt="">
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

<?php if ($geradeErstellt || $geradeGespeichert): ?>
<script src="/assets/auswahl.js"></script>
<script>
    (function () {
        if (window.PeterAuswahl) {
            <?php if ($geradeErstellt): ?>window.PeterAuswahl.loesche('auswahl_neu');<?php endif; ?>
            <?php if ($geradeGespeichert): ?>window.PeterAuswahl.loesche('auswahl_gruppe_<?= (int) $gruppe['id'] ?>');<?php endif; ?>
        }
    })();
</script>
<?php endif; ?>
