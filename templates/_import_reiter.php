<?php
/** @var string $aktiverReiter */
declare(strict_types=1);

$reiter = [
    'tabelle' => ['/import.php', t('import.reiter_tabelle')],
    'bilder' => ['/bilder_upload.php', t('import.reiter_bilder')],
    'orte' => ['/orte.php', t('import.reiter_orte')],
    'kuenstler' => ['/kuenstler.php', t('import.reiter_kuenstler')],
];
?>
<nav class="reiter">
    <?php foreach ($reiter as $schluessel => [$url, $text]): ?>
        <a href="<?= $url ?>" class="<?= $aktiverReiter === $schluessel ? 'aktiv' : '' ?>"><?= \App\Helpers::e($text) ?></a>
    <?php endforeach; ?>
</nav>
