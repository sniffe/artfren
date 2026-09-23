<?php
/** @var string $inhalt */
/** @var array|null $aktuellerBenutzer */
/** @var array $flashes */
declare(strict_types=1);

use App\Helpers;

$titel = $titel ?? APP_NAME;
$aktuelleSeite = $aktuelleSeite ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Helpers::e($titel) ?> · <?= Helpers::e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Source+Serif+4:ital,opsz@0,8..60;1,8..60&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/tokens.css">
<link rel="stylesheet" href="/assets/style.css">
<script>
(function () {
    try {
        var gespeichert = localStorage.getItem('theme');
        if (gespeichert) {
            document.documentElement.setAttribute('data-theme', gespeichert);
        }
    } catch (e) {}
})();
</script>
</head>
<body>
<?php if ($aktuellerBenutzer !== null): ?>
<header class="kopf">
    <div class="kopf__marke"><?= Helpers::e(APP_NAME) ?></div>
    <nav class="nav">
        <?php if (\App\Auth::isAdmin($aktuellerBenutzer)): ?>
        <a href="/werke.php" class="<?= $aktuelleSeite === 'werke' ? 'aktiv' : '' ?>">Werke</a>
        <?php endif; ?>
        <a href="/gruppen.php" class="<?= $aktuelleSeite === 'gruppen' ? 'aktiv' : '' ?>">Gruppen</a>
        <?php if (\App\Auth::isAdmin($aktuellerBenutzer)): ?>
        <a href="/import.php" class="<?= $aktuelleSeite === 'import' ? 'aktiv' : '' ?>">Import</a>
        <a href="/export_gesamt.php">Gesamtexport</a>
        <a href="/benutzer.php" class="<?= $aktuelleSeite === 'benutzer' ? 'aktiv' : '' ?>">Benutzer</a>
        <a href="/backup.php" class="<?= $aktuelleSeite === 'backup' ? 'aktiv' : '' ?>">Backup</a>
        <?php endif; ?>
    </nav>
    <div class="kopf__benutzer">
        <button type="button" class="theme-schalter" id="theme-schalter" aria-label="Farbschema umschalten">Hell/Dunkel</button>
        <span><?= Helpers::e($aktuellerBenutzer['echter_name'] ?: $aktuellerBenutzer['benutzername']) ?></span>
        <a href="/logout.php">Abmelden</a>
    </div>
</header>
<?php endif; ?>
<main class="hauptinhalt <?= !empty($breit) ? 'hauptinhalt--breit' : '' ?>">
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash--<?= Helpers::e($flash['typ']) ?>"><?= Helpers::e($flash['nachricht']) ?></div>
    <?php endforeach; ?>
    <?= $inhalt ?>
</main>
<script>
(function () {
    var schalter = document.getElementById('theme-schalter');
    if (!schalter) return;
    schalter.addEventListener('click', function () {
        var aktuell = document.documentElement.getAttribute('data-theme');
        var neu = aktuell === 'dunkel' ? 'hell' : 'dunkel';
        document.documentElement.setAttribute('data-theme', neu);
        try { localStorage.setItem('theme', neu); } catch (e) {}
    });
})();
</script>
</body>
</html>
