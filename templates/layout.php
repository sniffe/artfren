<?php
/** @var string $inhalt */
/** @var array|null $aktuellerBenutzer */
/** @var array $flashes */
declare(strict_types=1);

use App\Auth;
use App\Database;
use App\Helpers;

$titel = $titel ?? \App\Einstellungen::appName();
$aktuelleSeite = $aktuelleSeite ?? '';
$istAdmin = $aktuellerBenutzer !== null && Auth::isAdmin($aktuellerBenutzer);
$look = \App\Einstellungen::look('intern');
$schrift = \App\Einstellungen::schrift();
$iconStaerke = \App\Einstellungen::iconStaerke();
$akzentfarbe = \App\Einstellungen::akzentfarbe();
$hatLogo = \App\Einstellungen::logoPfad() !== null;

/** Gibt einen Nav-Link-String zurück (aktiv-Klasse wenn Seite übereinstimmt). */
$navLink = static function (string $url, string $seite, string $text) use ($aktuelleSeite): string {
    $klasse = 'nav__link' . ($aktuelleSeite === $seite ? ' aktiv' : '');
    return '<a href="' . $url . '" class="' . $klasse . '">' . Helpers::e($text) . '</a>';
};

/** Prüft ob die aktuelle Seite einer Gruppe angehört. */
$gruppeAktiv = static function (array $seiten) use ($aktuelleSeite): bool {
    return in_array($aktuelleSeite, $seiten, true);
};

// Backup-Erinnerung: letztes Backup > 30 Tage
$backupErinnerung = false;
if ($istAdmin && $aktuelleSeite !== 'backup') {
    try {
        $letzteBackupAm = Database::get()->query("SELECT MAX(erstellt_am) FROM backups")->fetchColumn();
        if ($letzteBackupAm === false || $letzteBackupAm === null) {
            $backupErinnerung = true;
        } else {
            $tage = (int) round((time() - strtotime((string) $letzteBackupAm)) / 86400);
            $backupErinnerung = $tage > 30;
        }
    } catch (\Throwable) {
        // Tabelle existiert noch nicht → kein Hinweis
    }
}
?>
<!DOCTYPE html>
<html lang="<?= Helpers::e(\App\I18n::aktiv()) ?>" data-look="<?= Helpers::e($look) ?>" data-schrift="<?= Helpers::e($schrift) ?>" data-icon-weight="<?= Helpers::e($iconStaerke) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Helpers::e($titel) ?> · <?= Helpers::e(\App\Einstellungen::appName()) ?></title>
<link rel="stylesheet" href="/assets/fonts.css">
<link rel="stylesheet" href="/assets/tokens.css">
<link rel="stylesheet" href="/assets/looks.css">
<link rel="stylesheet" href="/assets/style.css">
<?php if ($akzentfarbe !== null): ?>
<style>:root{--farbe-akzent:<?= Helpers::e($akzentfarbe) ?>;--farbe-akzent-text:<?= Helpers::e(\App\Einstellungen::akzentTextfarbe()) ?>}</style>
<?php endif; ?>
<script src="/assets/theme-init.js"></script>
</head>
<body>
<?php readfile(APP_ROOT . '/assets/icons.svg'); ?>
<?php if ($aktuellerBenutzer !== null): ?>
<header class="kopf">
    <a class="kopf__marke" href="<?= Helpers::e(Auth::startseite($aktuellerBenutzer)) ?>" title="<?= Helpers::e(t('nav.startseite_title')) ?>">
        <?php if ($hatLogo): ?>
            <img src="/logo.php" alt="<?= Helpers::e(\App\Einstellungen::appName()) ?>" style="max-height:40px; max-width:180px; object-fit:contain; display:block;">
        <?php else: ?>
            <?= Helpers::e(\App\Einstellungen::appName()) ?>
        <?php endif; ?>
    </a>
    <button type="button" class="nav-hamburger" id="nav-hamburger"
            aria-expanded="false" aria-controls="nav-inhalt" aria-label="<?= Helpers::e(t('nav.navigation_oeffnen')) ?>">
        <?= Helpers::icon('hamburger', 'nav-hamburger__icon') ?>
    </button>
    <div class="nav-inhalt" id="nav-inhalt">
        <nav class="nav">
            <?php if ($istAdmin): ?><?= $navLink('/werke.php', 'werke', t('nav.werke')) ?><?php endif; ?>
            <?= $navLink('/gruppen.php', 'gruppen', t('nav.gruppen')) ?>
            <?php if ($istAdmin): ?>
            <details class="nav-klappe" <?= $gruppeAktiv(['import', 'export', 'bilder', 'orte', 'backup']) ? 'open' : '' ?>>
                <summary class="nav__link <?= $gruppeAktiv(['import', 'export', 'bilder', 'orte', 'backup']) ? 'aktiv' : '' ?>"><?= Helpers::e(t('nav.daten')) ?> <?= Helpers::icon('pfeil-unten', 'nav-klappe__pfeil') ?></summary>
                <div class="nav-klappe__inhalt">
                    <a href="/import.php" class="<?= $aktuelleSeite === 'import' ? 'aktiv' : '' ?>"><?= Helpers::icon('hochladen') ?> <?= Helpers::e(t('nav.import')) ?></a>
                    <a href="/export_gesamt.php" class="<?= $aktuelleSeite === 'export' ? 'aktiv' : '' ?>"><?= Helpers::icon('herunterladen') ?> <?= Helpers::e(t('nav.export')) ?></a>
                    <a href="/bilder_upload.php" class="<?= $aktuelleSeite === 'bilder' ? 'aktiv' : '' ?>"><?= Helpers::icon('bild') ?> <?= Helpers::e(t('nav.bilder_hochladen')) ?></a>
                    <a href="/orte.php" class="<?= $aktuelleSeite === 'orte' ? 'aktiv' : '' ?>"><?= Helpers::icon('lupe') ?> <?= Helpers::e(t('nav.orte_bereinigen')) ?></a>
                    <a href="/backup.php" class="<?= $aktuelleSeite === 'backup' ? 'aktiv' : '' ?>"><?= Helpers::icon('datenbank') ?> <?= Helpers::e(t('nav.backup')) ?></a>
                </div>
            </details>
            <details class="nav-klappe" <?= $gruppeAktiv(['benutzer', 'datenpruefung', 'papierkorb', 'erscheinungsbild', 'rechtliche_angaben', 'system']) ? 'open' : '' ?>>
                <summary class="nav__link <?= $gruppeAktiv(['benutzer', 'datenpruefung', 'papierkorb', 'erscheinungsbild', 'rechtliche_angaben', 'system']) ? 'aktiv' : '' ?>"><?= Helpers::e(t('nav.verwaltung')) ?> <?= Helpers::icon('pfeil-unten', 'nav-klappe__pfeil') ?></summary>
                <div class="nav-klappe__inhalt">
                    <a href="/benutzer.php" class="<?= $aktuelleSeite === 'benutzer' ? 'aktiv' : '' ?>"><?= Helpers::icon('person') ?> <?= Helpers::e(t('nav.benutzer')) ?></a>
                    <a href="/datenpruefung.php" class="<?= $aktuelleSeite === 'datenpruefung' ? 'aktiv' : '' ?>"><?= Helpers::icon('haken') ?> <?= Helpers::e(t('nav.datenpruefung')) ?></a>
                    <a href="/papierkorb.php" class="<?= $aktuelleSeite === 'papierkorb' ? 'aktiv' : '' ?>"><?= Helpers::icon('papierkorb') ?> <?= Helpers::e(t('nav.papierkorb')) ?></a>
                    <a href="/erscheinungsbild.php" class="<?= $aktuelleSeite === 'erscheinungsbild' ? 'aktiv' : '' ?>"><?= Helpers::icon('palette') ?> <?= Helpers::e(t('nav.erscheinungsbild')) ?></a>
                    <a href="/rechtliche_angaben.php" class="<?= $aktuelleSeite === 'rechtliche_angaben' ? 'aktiv' : '' ?>"><?= Helpers::icon('dokument') ?> <?= Helpers::e(t('nav.rechtliche_angaben')) ?></a>
                    <a href="/system.php" class="<?= $aktuelleSeite === 'system' ? 'aktiv' : '' ?>"><?= Helpers::icon('zahnrad') ?> <?= Helpers::e(t('nav.system')) ?></a>
                </div>
            </details>
            <?php endif; ?>
        </nav>
        <details class="nav-klappe nav-klappe--benutzer">
            <summary class="nav__link nav__link--benutzer"><?= Helpers::icon('person', 'nav-klappe__person-icon') ?> <?= Helpers::e($aktuellerBenutzer['echter_name'] ?: $aktuellerBenutzer['benutzername']) ?> <?= Helpers::icon('pfeil-unten', 'nav-klappe__pfeil') ?></summary>
            <div class="nav-klappe__inhalt nav-klappe__inhalt--rechts">
                <a href="/konto.php"><?= Helpers::icon('person') ?> <?= Helpers::e(t('nav.mein_konto')) ?></a>
                <button type="button" class="nav-klappe__btn" id="theme-schalter"><?= Helpers::icon('sonne') ?> <?= Helpers::e(t('nav.hell_dunkel')) ?></button>
                <form method="post" action="/logout.php">
                    <?= Helpers::csrfField() ?>
                    <button type="submit" class="nav-klappe__btn"><?= Helpers::icon('abmelden') ?> <?= Helpers::e(t('nav.abmelden')) ?></button>
                </form>
            </div>
        </details>
    </div>
</header>
<?php endif; ?>
<main class="hauptinhalt <?= !empty($breit) ? 'hauptinhalt--breit' : '' ?>">
    <?php if ($istAdmin && ($aktuelleSeite ?? '') !== 'system' && \App\Wartung::fehlendeSchutzdateien() !== []): ?>
        <div class="flash flash--fehler"><?= Helpers::e(t('layout.schutzdateien_fehlen')) ?> <a href="/system.php"><?= Helpers::e(t('layout.schutzdateien_link')) ?></a></div>
    <?php endif; ?>
    <?php if ($istAdmin && ($aktuelleSeite ?? '') !== 'system' && \App\Wartung::veralteteDateien() !== []): ?>
        <div class="flash flash--hinweis"><?= Helpers::e(t('layout.veraltete_dateien')) ?> <a href="/system.php"><?= Helpers::e(t('layout.veraltete_dateien_link')) ?></a></div>
    <?php endif; ?>
    <?php if ($backupErinnerung): ?>
        <div class="flash flash--hinweis"><?= Helpers::e(t('layout.backup_fehlt')) ?> <a href="/backup.php"><?= Helpers::e(t('layout.backup_link')) ?></a></div>
    <?php endif; ?>
    <?php foreach ($flashes as $flash): ?>
        <div class="flash flash--<?= Helpers::e($flash['typ']) ?>"><?= Helpers::e($flash['nachricht']) ?></div>
    <?php endforeach; ?>
    <?= $inhalt ?>
</main>
<footer class="app-footer">
    <span class="text-sekundaer text-klein"><?= Helpers::e(\App\Einstellungen::appName()) ?> &middot; <?= Helpers::e(t('layout.version', ['v' => APP_VERSION])) ?></span>
</footer>
<script src="/assets/app.js"></script>
</body>
</html>
