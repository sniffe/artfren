<?php
/** @var array $gruppe */
/** @var array $werk */
/** @var array[] $bilder */
/** @var array[] $sichtbareFelder */
/** @var string $token */
/** @var int|null $prevId */
/** @var int|null $nextId */
/** @var bool $adminVorschau */
declare(strict_types=1);

use App\Helpers;

$hauptbild = $bilder[0] ?? null;
$bildUrl   = $hauptbild ? \App\WebGruppe::webBildUrl($token, (int) $hauptbild['id'], 'g') : null;
$meta = Helpers::werkMeta($werk['technik'] ?? null, $werk['entstehungsjahr'] ?? null);
$gruppenTitel = !empty($gruppe['web_titel']) ? $gruppe['web_titel'] : $gruppe['name'];
?>
<?php if ($adminVorschau): ?>
<div class="web-vorschau-banner"><?= Helpers::e(t('pubweb.vorschau_admin')) ?></div>
<?php endif; ?>
<div class="web-detail-nav">
    <a href="/w/<?= Helpers::e($token) ?>" class="web-detail-nav__zurueck">← <?= Helpers::e($gruppenTitel) ?></a>
    <div class="web-detail-nav__prev-next">
        <?php if ($prevId): ?>
            <a href="/w/<?= Helpers::e($token) ?>/werk/<?= (int) $prevId ?>" id="prev-link" title="<?= Helpers::e(t('pubweb.vorheriges_werk')) ?>">←</a>
        <?php else: ?>
            <span class="web-detail-nav__leer">←</span>
        <?php endif; ?>
        <?php if ($nextId): ?>
            <a href="/w/<?= Helpers::e($token) ?>/werk/<?= (int) $nextId ?>" id="next-link" title="<?= Helpers::e(t('pubweb.naechstes_werk')) ?>">→</a>
        <?php else: ?>
            <span class="web-detail-nav__leer">→</span>
        <?php endif; ?>
    </div>
</div>

<div class="web-detail">
    <div class="web-detail__bild-bereich">
        <div class="web-passepartout" id="web-hauptbild">
            <?php if ($bildUrl): ?>
                <img src="<?= Helpers::e($bildUrl) ?>" alt="<?= Helpers::e($werk['titel'] ?? '') ?>" id="web-hauptbild-img">
            <?php else: ?>
                <span class="web-karte__kein-bild"></span>
            <?php endif; ?>
        </div>

        <?php if (count($bilder) > 1): ?>
        <div class="web-galerie-streifen" id="web-galerie-streifen">
            <?php foreach ($bilder as $i => $bild): ?>
                <?php $tUrl = \App\WebGruppe::webBildUrl($token, (int) $bild['id'], 'm'); ?>
                <button type="button"
                        class="web-thumb<?= $i === 0 ? ' web-thumb--aktiv' : '' ?>"
                        data-bild-url="<?= Helpers::e(\App\WebGruppe::webBildUrl($token, (int) $bild['id'], 'g') ?? '') ?>"
                        data-bild-alt="<?= Helpers::e((string) ($bild['beschriftung'] ?? ($werk['titel'] ?? ''))) ?>"
                        aria-label="<?= Helpers::e(t('pubweb.bild_n', ['n' => $i + 1])) ?>">
                    <?php if ($tUrl): ?>
                        <img src="<?= Helpers::e($tUrl) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($hauptbild && !empty($werk['copyright'])): ?>
            <p class="web-copyright"><?= Helpers::e($werk['copyright']) ?></p>
        <?php endif; ?>
    </div>

    <div class="web-detail__info">
        <?php if (!empty($werk['maler'])): ?>
            <div class="web-detail__maler"><?= Helpers::e($werk['maler']) ?></div>
        <?php endif; ?>
        <?php if (!empty($werk['titel'])): ?>
            <h2 class="web-detail__titel"><?= Helpers::e($werk['titel']) ?></h2>
        <?php endif; ?>
        <?php if ($meta !== ''): ?>
            <div class="web-detail__meta"><?= Helpers::e($meta) ?></div>
        <?php endif; ?>

        <?php if ($sichtbareFelder): ?>
        <dl class="web-detail__felder">
            <?php foreach ($sichtbareFelder as $feld): ?>
                <?php
                $wert = $werk[$feld['key']] ?? null;
                if ($wert === null || $wert === '') { continue; }
                $formatiert = match ($feld['typ']) {
                    'geld'     => Helpers::formatGeld((float) $wert),
                    'langtext' => nl2br(Helpers::e((string) $wert)),
                    default    => Helpers::e((string) $wert),
                };
                ?>
                <dt><?= Helpers::e(t($feld['label'])) ?></dt>
                <dd><?= $formatiert ?></dd>
            <?php endforeach; ?>
        </dl>
        <?php endif; ?>
    </div>
</div>

<script src="<?= \App\Helpers::asset('/assets/web-galerie.js') ?>"></script>
