<?php
/** @var array|null $pruefung */
/** @var array<string, string> $info */
/** @var bool $httpsAktiv */
/** @var array $protokoll */
/** @var int $proSeite */
/** @var int $seite */
declare(strict_types=1);

use App\Helpers;
use App\Sicherheitscheck;

$ordnerTexte = [
    'data' => 'Datenbank und Protokolle',
    'backups' => 'Backup-Dateien',
    'bilder' => 'Original-Bilder',
    'src' => 'Programmcode',
];
$mehrSeiten = count($protokoll) > $proSeite;
$protokoll = array_slice($protokoll, 0, $proSeite);
?>
<h2>System</h2>

<div class="feldreihe">
    <div class="karte" style="flex:1;min-width:320px;">
        <h3>Sicherheitsprüfung</h3>
        <p class="text-klein text-sekundaer">Prüft per echtem Abruf von außen, ob geschützte Ordner erreichbar sind – etwa weil der Hoster <code>.htaccess</code> nicht auswertet.</p>
        <?php if ($pruefung): ?>
            <ul class="warnung-liste">
                <?php foreach ($pruefung['ergebnis'] as $ordner => $status): ?>
                    <li>
                        <code><?= Helpers::e($ordner) ?>/</code> (<?= Helpers::e($ordnerTexte[$ordner] ?? '') ?>):
                        <?php if ($status === Sicherheitscheck::GESCHUETZT): ?>
                            <span class="status-ok">geschützt</span>
                        <?php elseif ($status === Sicherheitscheck::OEFFENTLICH): ?>
                            <span class="status-schlecht">ÖFFENTLICH ERREICHBAR</span>
                        <?php else: ?>
                            <span class="text-sekundaer">nicht prüfbar</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (in_array(Sicherheitscheck::OEFFENTLICH, $pruefung['ergebnis'], true)): ?>
                <div class="flash flash--fehler">Der Webserver wertet die <code>.htaccess</code>-Sperren nicht aus. Bitte beim Hoster „AllowOverride“ für <code>.htaccess</code> aktivieren lassen oder die genannten Ordner in der Hoster-Verwaltung sperren (z. B. Verzeichnisschutz).</div>
            <?php endif; ?>
            <p class="text-klein text-sekundaer">Zuletzt geprüft: <?= date('d.m.Y H:i', (int) $pruefung['zeit']) ?></p>
        <?php endif; ?>
        <form method="post" action="/system.php">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="pruefen">
            <button type="submit" class="btn btn--primaer">Jetzt prüfen</button>
        </form>
        <?php if (!$httpsAktiv): ?>
            <div class="flash flash--hinweis mt-m">Die Seite wird unverschlüsselt (HTTP) aufgerufen. Passwörter werden so im Klartext übertragen. Bei fast allen Hostern lässt sich ein kostenloses SSL-Zertifikat (Let’s Encrypt) aktivieren; danach in <code>src/config.php</code> <code>HTTPS_ERZWINGEN</code> auf <code>true</code> setzen.</div>
        <?php endif; ?>
    </div>

    <div class="karte" style="flex:1;min-width:320px;">
        <h3>Server</h3>
        <table class="liste">
            <?php foreach ($info as $name => $wert): ?>
                <tr><td class="text-klein text-sekundaer"><?= Helpers::e($name) ?></td><td class="text-klein"><?= Helpers::e($wert) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<h3 class="mt-l">Protokoll</h3>
<table class="liste">
    <thead>
        <tr><th>Zeitpunkt</th><th>Benutzer</th><th>Aktion</th><th>Details</th><th>IP</th></tr>
    </thead>
    <tbody>
    <?php foreach ($protokoll as $p): ?>
        <tr>
            <td class="text-klein" style="white-space:nowrap;"><?= Helpers::e(Helpers::formatDatum($p['zeitpunkt'], 'd.m.Y H:i:s')) ?></td>
            <td class="text-klein"><?= Helpers::e($p['benutzername'] ?? '–') ?></td>
            <td class="text-klein"><code><?= Helpers::e($p['aktion']) ?></code></td>
            <td class="text-klein"><?= Helpers::e($p['details']) ?></td>
            <td class="text-klein text-sekundaer"><?= Helpers::e($p['ip']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$protokoll): ?>
        <tr><td colspan="5" class="text-sekundaer">Noch keine Einträge.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<div class="seiten-nav">
    <?php if ($seite > 1): ?><a class="btn btn--klein" href="/system.php?seite=<?= $seite - 1 ?>">← neuere</a><?php endif; ?>
    <?php if ($mehrSeiten): ?><a class="btn btn--klein" href="/system.php?seite=<?= $seite + 1 ?>">ältere →</a><?php endif; ?>
</div>
