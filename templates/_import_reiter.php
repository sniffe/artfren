<?php
/** @var string $aktiverReiter */
declare(strict_types=1);

$reiter = [
    'tabelle' => ['/import.php', 'Tabelle importieren'],
    'bilder' => ['/bilder_upload.php', 'Bilder hochladen'],
    'orte' => ['/orte.php', 'Orte bereinigen'],
];
?>
<nav class="reiter">
    <?php foreach ($reiter as $schluessel => [$url, $text]): ?>
        <a href="<?= $url ?>" class="<?= $aktiverReiter === $schluessel ? 'aktiv' : '' ?>"><?= $text ?></a>
    <?php endforeach; ?>
</nav>
