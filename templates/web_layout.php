<?php
/** @var string $inhalt */
/** @var string $titel */
/** @var string $appName */
declare(strict_types=1);

use App\Einstellungen;
use App\Helpers;

$seitentitel = isset($titel) && $titel !== '' ? Helpers::e($titel) . ' – ' . Helpers::e($appName) : Helpers::e($appName);
$lookWeb     = Einstellungen::look('web');
$akzentfarbe = Einstellungen::akzentfarbe();
$hatLogo     = Einstellungen::logoPfad() !== null;
?>
<!DOCTYPE html>
<html lang="de" data-look="<?= Helpers::e($lookWeb) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $seitentitel ?></title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="stylesheet" href="/assets/tokens.css">
    <link rel="stylesheet" href="/assets/looks.css">
    <link rel="stylesheet" href="/assets/web.css">
    <?php if ($akzentfarbe !== null): ?>
    <style>:root{--farbe-akzent:<?= Helpers::e($akzentfarbe) ?>;--farbe-akzent-text:<?= Helpers::e(Einstellungen::akzentTextfarbe()) ?>}</style>
    <?php endif; ?>
    <script src="/assets/theme-init.js"></script>
</head>
<body>
<?php echo $inhalt; ?>
<?php
// Impressum/Datenschutz Footer
$pdo = App\Database::get();
$stmtImpressum = $pdo->prepare('SELECT wert FROM einstellungen WHERE schluessel = :s');
$stmtImpressum->execute(['s' => 'impressum_url']);
$impressumUrl = (string) ($stmtImpressum->fetchColumn() ?: '');
$stmtImpressum->execute(['s' => 'impressum_text']);
$hatImpressum = $impressumUrl !== '' || ($stmtImpressum->fetchColumn() ?: '') !== '';
$stmtDatenschutz = $pdo->prepare('SELECT wert FROM einstellungen WHERE schluessel = :s');
$stmtDatenschutz->execute(['s' => 'datenschutz_url']);
$datenschutzUrl = (string) ($stmtDatenschutz->fetchColumn() ?: '');
$stmtDatenschutz->execute(['s' => 'datenschutz_text']);
$hatDatenschutz = $datenschutzUrl !== '' || ($stmtDatenschutz->fetchColumn() ?: '') !== '';
$linkImpressum  = $impressumUrl !== '' ? $impressumUrl : '/w/impressum';
$linkDatenschutz = $datenschutzUrl !== '' ? $datenschutzUrl : '/w/datenschutz';
if ($hatImpressum || $hatDatenschutz):
?>
<footer class="web-footer">
    <?php if ($hatImpressum): ?>
        <a href="<?= Helpers::e($linkImpressum) ?>"<?= $impressumUrl !== '' ? ' rel="noopener"' : '' ?>>Impressum</a>
    <?php endif; ?>
    <?php if ($hatImpressum && $hatDatenschutz): ?> · <?php endif; ?>
    <?php if ($hatDatenschutz): ?>
        <a href="<?= Helpers::e($linkDatenschutz) ?>"<?= $datenschutzUrl !== '' ? ' rel="noopener"' : '' ?>>Datenschutz</a>
    <?php endif; ?>
</footer>
<?php endif; ?>
<script src="/assets/app.js"></script>
</body>
</html>
