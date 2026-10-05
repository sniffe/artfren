<?php
/** @var array $gruppe */
/** @var array[] $werke */
/** @var string $token */
/** @var bool $adminVorschau */
declare(strict_types=1);

use App\Helpers;

$gruppenTitel = !empty($gruppe['web_titel']) ? $gruppe['web_titel'] : $gruppe['name'];
$einleitung   = $gruppe['web_einleitung'] ?? '';
?>
<?php if ($adminVorschau): ?>
<div class="web-vorschau-banner">Vorschau (Admin) – diese Galerie ist <?= (int) $gruppe['web_aktiv'] ? 'aktiv' : 'noch nicht veröffentlicht' ?></div>
<?php endif; ?>
<header class="web-header">
    <h1><?= Helpers::e($gruppenTitel) ?></h1>
    <?php if ($einleitung !== ''): ?>
        <p class="web-einleitung"><?= nl2br(Helpers::e($einleitung)) ?></p>
    <?php endif; ?>
</header>

<?php if (!$werke): ?>
    <p class="web-leer">Diese Galerie enthält noch keine Werke.</p>
<?php else: ?>
<div class="web-karten" id="web-karten">
    <?php foreach ($werke as $w): ?>
        <?php
        $bUrl  = \App\WebGruppe::webBildUrl($token, $w['bild_id'] ?? null, 'm');
        $wUrl  = '/w/' . $token . '/werk/' . (int) $w['id'];
        $meta  = Helpers::werkMeta($w['technik'] ?? null, $w['entstehungsjahr'] ?? null);
        ?>
        <a class="web-karte" href="<?= Helpers::e($wUrl) ?>">
            <div class="web-karte__bild">
                <?php if ($bUrl): ?>
                    <img src="<?= Helpers::e($bUrl) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <span class="web-karte__kein-bild"></span>
                <?php endif; ?>
                <?php if (($w['bild_anzahl'] ?? 1) > 1): ?>
                    <span class="bild-anzahl-badge">+<?= (int) $w['bild_anzahl'] - 1 ?></span>
                <?php endif; ?>
            </div>
            <div class="web-karte__etikett">
                <?php if (!empty($w['maler'])): ?>
                    <div class="web-karte__maler"><?= Helpers::e($w['maler']) ?></div>
                <?php endif; ?>
                <?php if (!empty($w['titel'])): ?>
                    <div class="web-karte__titel"><?= Helpers::e($w['titel']) ?></div>
                <?php endif; ?>
                <?php if ($meta !== ''): ?>
                    <div class="web-karte__meta"><?= Helpers::e($meta) ?></div>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
