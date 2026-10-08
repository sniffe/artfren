<?php
/** @var array|null $pruefung */
/** @var array<string, string> $info */
/** @var array<int, string> $warnungen */
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
    'data'    => t('system.ordner_data'),
    'backups' => t('system.ordner_backups'),
    'bilder'  => t('system.ordner_bilder'),
    'src'     => t('system.ordner_src'),
];
$fehlendNachOrdner = [];
foreach ($vollstaendigkeit['fehlend'] ?? [] as $pfad) {
    $fehlendNachOrdner[str_contains($pfad, '/') ? strstr($pfad, '/', true) . '/' : t('system.ordner_hauptordner')][] = $pfad;
}
$mehrSeiten = count($protokoll) > $proSeite;
$protokoll = array_slice($protokoll, 0, $proSeite);
?>
<h2><?= Helpers::e(t('system.titel')) ?></h2>

<div class="karte">
    <h3><?= Helpers::e(t('system.vollstaendigkeit')) ?></h3>
    <?php if ($vollstaendigkeit === null): ?>
        <p class="text-sekundaer"><?= Helpers::e(t('system.dateiliste_fehlt', ['datei' => \App\Wartung::DATEILISTE])) ?></p>
    <?php elseif ($vollstaendigkeit['fehlend'] === []): ?>
        <p class="status-ok"><?= Helpers::e(\App\I18n::plural($vollstaendigkeit['gesamt'], 'system.alle_vorhanden', ['n' => $vollstaendigkeit['gesamt']])) ?></p>
    <?php else: ?>
        <div class="flash flash--fehler"><?= Helpers::e(t('system.dateien_fehlen', ['fehlend' => count($vollstaendigkeit['fehlend']), 'gesamt' => $vollstaendigkeit['gesamt']])) ?></div>
        <ul class="warnung-liste">
            <?php foreach ($fehlendNachOrdner as $ordner => $pfade): ?>
                <li><code><?= Helpers::e($ordner) ?></code>: <?= count($pfade) ?> <?= Helpers::e(t('system.fehlend_label')) ?><br>
                    <span class="text-klein text-sekundaer"><?= Helpers::e(t('system.fehlend_bsp', ['pfade' => implode(', ', array_slice($pfade, 0, 5)) . (count($pfade) > 5 ? ' …' : '')])) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($fehlendeSchutzdateien): ?>
        <div class="flash flash--fehler"><?= Helpers::e(t('system.schutzdateien_fehlen', ['dateien' => implode(', ', $fehlendeSchutzdateien)])) ?></div>
    <?php endif; ?>
</div>

<?php if ($veraltet): ?>
    <div class="karte">
        <h3><?= Helpers::e(t('system.veraltet_titel')) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('system.veraltet_hinweis')) ?></p>
        <ul class="warnung-liste">
            <?php foreach ($veraltet as $pfad => $beschreibung): ?>
                <li><code><?= Helpers::e($pfad) ?></code> – <?= Helpers::e($beschreibung) ?></li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="/system.php">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="aufraeumen">
            <button type="submit" class="btn btn--primaer" data-bestaetigen="<?= Helpers::e(t('system.veraltet_loeschen_frage')) ?>"><?= Helpers::e(t('system.veraltet_loeschen')) ?></button>
        </form>
    </div>
<?php endif; ?>

<div class="karte">
    <h3><?= Helpers::e(t('system.einstellungen')) ?></h3>
    <form method="post" action="/system.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="einstellungen">
        <div class="feld">
            <label for="app_name"><?= Helpers::e(t('system.app_name')) ?></label>
            <input type="text" id="app_name" name="app_name" required maxlength="80" value="<?= Helpers::e($appName) ?>">
        </div>
        <div class="feld">
            <label>
                <input type="checkbox" name="https_erzwingen" value="1" <?= $httpsErzwingen ? 'checked' : '' ?> <?= !$httpsAktiv && !$httpsErzwingen ? 'disabled' : '' ?>>
                <?= Helpers::e(t('system.https_erzwingen')) ?>
            </label>
            <?php if (!$httpsAktiv): ?>
                <p class="text-klein text-sekundaer"><?= Helpers::e(t('system.https_hinweis')) ?></p>
            <?php endif; ?>
        </div>
        <div class="feld">
            <label for="standard_sprache"><?= Helpers::e(t('system.standardsprache')) ?></label>
            <select name="standard_sprache" id="standard_sprache">
                <?php foreach (\App\I18n::SPRACHEN as $code => $name): ?>
                    <option value="<?= Helpers::e($code) ?>" <?= ($standardSprache ?? 'de') === $code ? 'selected' : '' ?>><?= Helpers::e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('allg.speichern')) ?></button>
    </form>
</div>

<div class="feldreihe">
    <div class="karte" style="flex:1;min-width:320px;">
        <h3><?= Helpers::e(t('system.sicherheitspruefung')) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('system.sicherheit_beschr')) ?></p>
        <?php if ($pruefung): ?>
            <ul class="warnung-liste">
                <?php foreach ($pruefung['ergebnis'] as $ordner => $status): ?>
                    <li>
                        <code><?= Helpers::e($ordner) ?>/</code> (<?= Helpers::e($ordnerTexte[$ordner] ?? '') ?>):
                        <?php if ($status === Sicherheitscheck::GESCHUETZT): ?>
                            <span class="status-ok"><?= Helpers::e(t('system.sicherheit_geschuetzt')) ?></span>
                        <?php elseif ($status === Sicherheitscheck::OEFFENTLICH): ?>
                            <span class="status-schlecht"><?= Helpers::e(t('system.sicherheit_oeffentlich')) ?></span>
                        <?php else: ?>
                            <span class="text-sekundaer"><?= Helpers::e(t('system.sicherheit_unbekannt')) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (in_array(Sicherheitscheck::OEFFENTLICH, $pruefung['ergebnis'], true)): ?>
                <div class="flash flash--fehler"><?= Helpers::e(t('system.sicherheit_fehler')) ?></div>
            <?php endif; ?>
            <p class="text-klein text-sekundaer"><?= Helpers::e(t('system.sicherheit_zuletzt', ['datum' => date('d.m.Y H:i', (int) $pruefung['zeit'])])) ?></p>
        <?php endif; ?>
        <form method="post" action="/system.php">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="aktion" value="pruefen">
            <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('system.sicherheit_starten')) ?></button>
        </form>
        <?php if (!$httpsAktiv): ?>
            <div class="flash flash--hinweis mt-m"><?= Helpers::e(t('system.https_warnung')) ?></div>
        <?php endif; ?>
    </div>

    <div class="karte" style="flex:1;min-width:320px;">
        <h3><?= Helpers::e(t('system.server_info')) ?></h3>
        <?php if ($warnungen): ?>
            <?php foreach ($warnungen as $warnung): ?>
                <div class="flash flash--fehler"><?= Helpers::e($warnung) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <table class="liste">
            <?php foreach ($info as $name => $wert): ?>
                <tr><td class="text-klein text-sekundaer"><?= Helpers::e($name) ?></td><td class="text-klein"><?= Helpers::e($wert) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<h3 class="mt-l"><?= Helpers::e(t('system.protokoll')) ?></h3>
<table class="liste">
    <thead>
        <tr><th><?= Helpers::e(t('system.prot_zeitpunkt')) ?></th><th><?= Helpers::e(t('system.prot_benutzer')) ?></th><th><?= Helpers::e(t('system.prot_aktion')) ?></th><th><?= Helpers::e(t('system.prot_details')) ?></th><th><?= Helpers::e(t('system.prot_ip')) ?></th></tr>
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
        <tr><td colspan="5" class="text-sekundaer"><?= Helpers::e(t('system.protokoll_leer')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
<div class="seiten-nav">
    <?php if ($seite > 1): ?><a class="btn btn--klein" href="/system.php?seite=<?= $seite - 1 ?>"><?= Helpers::e(t('system.prot_neuere')) ?></a><?php endif; ?>
    <?php if ($mehrSeiten): ?><a class="btn btn--klein" href="/system.php?seite=<?= $seite + 1 ?>"><?= Helpers::e(t('system.protokoll_mehr')) ?></a><?php endif; ?>
</div>
