<?php
/** @var array|null $pruefung */
/** @var array<string, string> $info */
/** @var bool $httpsAktiv */
/** @var string $appName */
/** @var bool $httpsErzwingen */
/** @var array<string, string> $veraltet */
/** @var array{gesamt: int, fehlend: string[]}|null $vollstaendigkeit */
/** @var string[] $fehlendeSchutzdateien */
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
$fehlendNachOrdner = [];
foreach ($vollstaendigkeit['fehlend'] ?? [] as $pfad) {
    $fehlendNachOrdner[str_contains($pfad, '/') ? strstr($pfad, '/', true) . '/' : 'Hauptordner'][] = $pfad;
}
$mehrSeiten = count($protokoll) > $proSeite;
$protokoll = array_slice($protokoll, 0, $proSeite);
?>
<h2>System</h2>

<div class="karte">
    <h3>Vollständigkeit des Programms</h3>
    <?php if ($vollstaendigkeit === null): ?>
        <p class="text-sekundaer">Die Dateiliste <code><?= Helpers::e(\App\Wartung::DATEILISTE) ?></code> fehlt, daher ist keine Prüfung möglich. Bitte den Ordner <code>src/</code> erneut hochladen.</p>
    <?php elseif ($vollstaendigkeit['fehlend'] === []): ?>
        <p class="status-ok">Alle <?= $vollstaendigkeit['gesamt'] ?> Programmdateien sind vorhanden.</p>
    <?php else: ?>
        <div class="flash flash--fehler"><?= count($vollstaendigkeit['fehlend']) ?> von <?= $vollstaendigkeit['gesamt'] ?> Programmdateien fehlen. Bitte die genannten Ordner aus dem heruntergeladenen Paket erneut hochladen und „Überschreiben“ wählen.</div>
        <ul class="warnung-liste">
            <?php foreach ($fehlendNachOrdner as $ordner => $pfade): ?>
                <li><code><?= Helpers::e($ordner) ?></code>: <?= count($pfade) ?> fehlend<br>
                    <span class="text-klein text-sekundaer">z. B. <?= Helpers::e(implode(', ', array_slice($pfade, 0, 5))) ?><?= count($pfade) > 5 ? ' …' : '' ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($fehlendeSchutzdateien): ?>
        <div class="flash flash--fehler">Es fehlen Schutzdateien: <?= Helpers::e(implode(', ', $fehlendeSchutzdateien)) ?>. Ohne sie können private Ordner von außen abrufbar sein. Im FTP-Programm „versteckte Dateien anzeigen“ einschalten (FileZilla: Server → Anzeigen versteckter Dateien erzwingen) und das Paket erneut hochladen.</div>
    <?php endif; ?>
</div>

<?php if ($veraltet): ?>
    <div class="karte">
        <h3>Veraltete Dateien aus früheren Versionen</h3>
        <p class="text-klein text-sekundaer">Beim Update per Hochladen bleiben Dateien, die es in der neuen Version nicht mehr gibt, auf dem Server liegen. Sie werden nicht mehr gebraucht und sollten entfernt werden.</p>
        <ul class="warnung-liste">
            <?php foreach ($veraltet as $pfad => $beschreibung): ?>
                <li><code><?= Helpers::e($pfad) ?></code> – <?= Helpers::e($beschreibung) ?></li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="/system.php">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="aufraeumen">
            <button type="submit" class="btn btn--primaer" data-bestaetigen="Die aufgelisteten veralteten Dateien jetzt endgültig löschen?">Jetzt löschen</button>
        </form>
    </div>
<?php endif; ?>

<div class="karte">
    <h3>Einstellungen</h3>
    <p class="text-klein text-sekundaer">Werden in der Datenbank gespeichert und bleiben bei Updates erhalten.</p>
    <form method="post" action="/system.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="einstellungen">
        <div class="feld">
            <label for="app_name">Name der Anwendung (links oben und im Login)</label>
            <input type="text" id="app_name" name="app_name" required maxlength="80" value="<?= Helpers::e($appName) ?>">
        </div>
        <div class="feld">
            <label>
                <input type="checkbox" name="https_erzwingen" value="1" <?= $httpsErzwingen ? 'checked' : '' ?> <?= !$httpsAktiv && !$httpsErzwingen ? 'disabled' : '' ?>>
                HTTPS erzwingen (unverschlüsselte Aufrufe werden auf https:// umgeleitet)
            </label>
            <?php if (!$httpsAktiv): ?>
                <p class="text-klein text-sekundaer">Nur einschaltbar, wenn diese Seite über <code>https://</code> aufgerufen wird – so ist sichergestellt, dass das Zertifikat funktioniert und man sich nicht aussperrt.</p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn--primaer">Speichern</button>
    </form>
</div>

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
            <div class="flash flash--hinweis mt-m">Die Seite wird unverschlüsselt (HTTP) aufgerufen. Passwörter werden so im Klartext übertragen. Bei fast allen Hostern lässt sich ein kostenloses SSL-Zertifikat (Let’s Encrypt) aktivieren; danach die Seite über <code>https://</code> aufrufen und oben unter „Einstellungen“ „HTTPS erzwingen“ einschalten.</div>
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
