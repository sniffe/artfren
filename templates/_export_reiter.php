<?php
/** @var string $aktiverReiter */
declare(strict_types=1);

$reiter = [
    'gesamt'  => ['/export_gesamt.php', t('export.reiter_gesamt')],
    'profile' => ['/export_profile.php', t('export.reiter_profile')],
];
?>
<nav class="reiter">
    <?php foreach ($reiter as $schluessel => [$url, $text]): ?>
        <a href="<?= $url ?>" class="<?= $aktiverReiter === $schluessel ? 'aktiv' : '' ?>"><?= \App\Helpers::e($text) ?></a>
    <?php endforeach; ?>
</nav>
