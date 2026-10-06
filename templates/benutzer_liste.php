<?php
/** @var array $benutzerListe */
/** @var array $gruppen */
/** @var array<int, int[]> $zuordnungen */
/** @var array $aktuellerBenutzer */
declare(strict_types=1);

use App\Helpers;

$gruppenNamen = array_column($gruppen, 'name', 'id');
$jetzt = gmdate('Y-m-d H:i:s');
?>
<h2><?= Helpers::e(t('benutzer.titel')) ?></h2>

<table class=”liste”>
    <thead>
        <tr>
            <th><?= Helpers::e(t('benutzer.benutzername')) ?></th>
            <th><?= Helpers::e(t('benutzer.name_email')) ?></th>
            <th><?= Helpers::e(t('benutzer.rolle')) ?></th>
            <th><?= Helpers::e(t('benutzer.letzter_login')) ?></th>
            <th><?= Helpers::e(t('benutzer.status')) ?></th>
            <th><?= Helpers::e(t('benutzer.aktionen')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($benutzerListe as $b): $id = (int) $b['id']; $gesperrt = !empty($b['gesperrt_bis']) && $b['gesperrt_bis'] > $jetzt; ?>
        <tr>
            <td><?= Helpers::e($b['benutzername']) ?><?= $id === (int) $aktuellerBenutzer['id'] ? ' <span class=”text-klein text-sekundaer”>' . Helpers::e(t('benutzer.du')) . '</span>' : '' ?></td>
            <td>
                <?= Helpers::e($b['echter_name']) ?>
                <?php if ($b['email']): ?><div class=”text-klein text-sekundaer”><?= Helpers::e($b['email']) ?></div><?php endif; ?>
            </td>
            <td>
                <span class=”badge <?= $b['rolle'] === 'admin' ? 'badge--admin' : '' ?>”><?= $b['rolle'] === 'admin' ? Helpers::e(t('benutzer.admin')) : Helpers::e(t('benutzer.eingeschraenkt')) ?></span>
                <?php if ($b['rolle'] === 'eingeschraenkt'): ?>
                    <div class=”text-klein text-sekundaer”>
                        <?php
                        $namen = array_filter(array_map(static fn(int $gid) => $gruppenNamen[$gid] ?? null, $zuordnungen[$id] ?? []));
                        echo $namen ? Helpers::e(implode(', ', $namen)) : Helpers::e(t('benutzer.keine_gruppen'));
                        ?>
                    </div>
                <?php endif; ?>
            </td>
            <td class=”text-klein”><?= Helpers::e(Helpers::formatDatum($b['letzter_login'])) ?: '–' ?></td>
            <td>
                <?php if ($gesperrt): ?>
                    <span class=”badge badge--gesperrt”><?= Helpers::e(t('benutzer.gesperrt_bis', ['zeit' => Helpers::formatDatum($b['gesperrt_bis'], 'H:i')])) ?></span>
                <?php else: ?>
                    <span class=”text-klein text-sekundaer”><?= Helpers::e(t('benutzer.aktiv')) ?></span>
                <?php endif; ?>
            </td>
            <td class=”aktionen”>
                <a href=”/benutzer_bearbeiten.php?id=<?= $id ?>” class=”btn btn--klein”><?= Helpers::e(t('allg.bearbeiten')) ?></a>
                <?php if ($gesperrt): ?>
                    <form method=”post” action=”/benutzer.php”>
                        <?= Helpers::csrfField() ?>
                        <input type=”hidden” name=”aktion” value=”entsperren”>
                        <input type=”hidden” name=”id” value=”<?= $id ?>”>
                        <button type=”submit” class=”btn btn--klein”><?= Helpers::e(t('benutzer.entsperren')) ?></button>
                    </form>
                <?php endif; ?>
                <?php if ($id !== (int) $aktuellerBenutzer['id']): ?>
                    <form method=”post” action=”/benutzer.php” data-bestaetigen=”<?= Helpers::e(t('benutzer.loeschen_frage', ['name' => $b['benutzername']])) ?>”>
                        <?= Helpers::csrfField() ?>
                        <input type=”hidden” name=”aktion” value=”loeschen”>
                        <input type=”hidden” name=”id” value=”<?= $id ?>”>
                        <button type=”submit” class=”btn btn--klein btn--gefahr”><?= Helpers::e(t('allg.loeschen')) ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class=”karte mt-l” style=”max-width:820px;”>
    <h3><?= Helpers::e(t('benutzer.neu_anlegen')) ?></h3>
    <form method=”post” action=”/benutzer.php” autocomplete=”off”>
        <?= Helpers::csrfField() ?>
        <input type=”hidden” name=”aktion” value=”anlegen”>
        <div class=”feldreihe”>
            <div class=”feld”>
                <label for=”benutzername”><?= Helpers::e(t('benutzer.benutzername')) ?></label>
                <input type=”text” id=”benutzername” name=”benutzername” required minlength=”3” maxlength=”50”>
            </div>
            <div class=”feld”>
                <label for=”echter_name”><?= Helpers::e(t('benutzer_bearbeiten.echter_name')) ?></label>
                <input type=”text” id=”echter_name” name=”echter_name” maxlength=”100”>
            </div>
            <div class=”feld”>
                <label for=”email”><?= Helpers::e(t('konto.email')) ?></label>
                <input type=”email” id=”email” name=”email”>
            </div>
        </div>
        <div class=”feldreihe”>
            <div class=”feld”>
                <label for=”passwort”><?= Helpers::e(t('benutzer.passwort')) ?></label>
                <input type=”password” id=”passwort” name=”passwort” required minlength=”8” autocomplete=”new-password”>
            </div>
            <div class=”feld”>
                <label for=”passwort_wiederholen”><?= Helpers::e(t('benutzer.passwort_wiederholen')) ?></label>
                <input type=”password” id=”passwort_wiederholen” name=”passwort_wiederholen” required minlength=”8” autocomplete=”new-password”>
            </div>
            <div class=”feld”>
                <label for=”rolle”><?= Helpers::e(t('benutzer.rolle')) ?></label>
                <select id=”rolle” name=”rolle” data-rolle-umschalter=”#gruppen-auswahl-neu”>
                    <option value=”eingeschraenkt”><?= Helpers::e(t('benutzer_bearbeiten.rolle_eingeschraenkt')) ?></option>
                    <option value=”admin”><?= Helpers::e(t('benutzer_bearbeiten.rolle_admin')) ?></option>
                </select>
            </div>
        </div>
        <?php $ausgewaehlt = []; $auswahlId = 'gruppen-auswahl-neu'; require __DIR__ . '/_gruppen_auswahl.php'; ?>
        <button type=”submit” class=”btn btn--primaer”><?= Helpers::e(t('benutzer.benutzer_anlegen')) ?></button>
    </form>
</div>
