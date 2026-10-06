<?php
/** @var string $titel */
/** @var string $inhaltText */
declare(strict_types=1);

use App\Helpers;
?>
<div class="web-legal">
    <h1><?= Helpers::e($titel) ?></h1>
    <?php if (trim($inhaltText) !== ''): ?>
        <?php foreach (preg_split('/\n{2,}/', trim($inhaltText)) ?: [] as $absatz): ?>
            <p><?= nl2br(Helpers::e(trim($absatz))) ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="web-leer"><?= Helpers::e(t('pubweb.kein_inhalt')) ?></p>
    <?php endif; ?>
    <p><a href="/" data-zurueck><?= Helpers::e(t('pubweb.zurueck')) ?></a></p>
</div>
