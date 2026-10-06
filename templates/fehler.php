<?php
/** @var string $titel */
/** @var string $text */
declare(strict_types=1);

use App\Helpers;
?>
<div class="karte" style="max-width:560px;">
    <h2><?= Helpers::e($titel) ?></h2>
    <p><?= Helpers::e($text) ?></p>
    <a href="/" class="btn"><?= Helpers::e(t('fehler.zurueck')) ?></a>
</div>
