<?php
/** @var string $inhalt */
/** @var array|null $aktuellerBenutzer */
/** @var array $flashes */
declare(strict_types=1);

use App\Auth;
use App\Helpers;

$titel = $titel ?? APP_NAME;
$aktuelleSeite = $aktuelleSeite ?? '';
$istAdmin = $aktuellerBenutzer !== null && Auth::isAdmin($aktuellerBenutzer);

$navLink = static function (string $url, string $seite, string $text) use ($aktuelleSeite): string {
    return '<a href="' . $url . '" class="' . ($aktuelleSeite === $seite ? 'aktiv' : '') . '">' . $text . '</a>';
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Helpers::e($titel) ?> · <?= Helpers::e(APP_NAME) ?></title>
<link rel="stylesheet" href="/assets/fonts.css">
<link rel="stylesheet" href="/assets/tokens.css">
<link rel="stylesheet" href="/assets/style.css">
<script src="/assets/theme-init.js"></script>
</head>
<body>
<?php if ($aktuellerBenutzer !== null): ?>
<header class="kopf">
    <a class="kopf__marke" href="<?= Auth::startseite($aktuellerBenutzer) ?>" title="Zur Startseite"><?= Helpers::e(APP_NAME) ?></a>
    <nav class="nav">
        <?php if ($istAdmin): ?><?= $navLink('/werke.php', 'werke', 'Werke') ?><?php endif; ?>
        <?= $navLink('/gruppen.php', 'gruppen', 'Gruppen') ?>
        <?php if ($istAdmin): ?>
            <?= $navLink('/import.php', 'import', 'Import') ?>
            <?= $navLink('/export_gesamt.php', 'export', 'Export') ?>
            <?= $navLink('/benutzer.php', 'benutzer', 'Benutzer') ?>
            <?= $navLink('/backup.php', 'backup', 'Backup') ?>
            <?= $navLink('/system.php', 'system', 'System') ?>
        <?php endif; ?>
    </nav>
    <div class="kopf__benutzer">
        <button type="button" class="theme-schalter" id="theme-schalter" aria-label="Farbschema umschalten">Hell/Dunkel</button>
        <a href="/konto.php" title="Mein Konto"><?= Helpers::e($aktuellerBenutzer['echter_name'] ?: $aktuellerBenutzer['benutzername']) ?></a>
        <form method="post" action="/logout.php">
            <?= Helpers::csrfField() ?>
            <button type="submit" class="link-button">Abmelden</button>
        </form>
    </div>
</header>
<?php endif; ?>
<main class="hauptinhalt <?= !empty($breit) ? 'hauptinhalt--breit' : '' ?>">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash--<?= Helpers::e($flash['typ']) ?>"><?= Helpers::e($flash['nachricht']) ?></div>
    <?php endforeach; ?>
    <?= $inhalt ?>
</main>
<script src="/assets/app.js"></script>
</body>
</html>
