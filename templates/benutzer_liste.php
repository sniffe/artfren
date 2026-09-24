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
<h2>Benutzerverwaltung</h2>

<table class="liste">
    <thead>
        <tr>
            <th>Benutzername</th>
            <th>Name / E-Mail</th>
            <th>Rolle</th>
            <th>Letzter Login</th>
            <th>Status</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($benutzerListe as $b): $id = (int) $b['id']; $gesperrt = !empty($b['gesperrt_bis']) && $b['gesperrt_bis'] > $jetzt; ?>
        <tr>
            <td><?= Helpers::e($b['benutzername']) ?><?= $id === (int) $aktuellerBenutzer['id'] ? ' <span class="text-klein text-sekundaer">(du)</span>' : '' ?></td>
            <td>
                <?= Helpers::e($b['echter_name']) ?>
                <?php if ($b['email']): ?><div class="text-klein text-sekundaer"><?= Helpers::e($b['email']) ?></div><?php endif; ?>
            </td>
            <td>
                <span class="badge <?= $b['rolle'] === 'admin' ? 'badge--admin' : '' ?>"><?= $b['rolle'] === 'admin' ? 'Admin' : 'Eingeschränkt' ?></span>
                <?php if ($b['rolle'] === 'eingeschraenkt'): ?>
                    <div class="text-klein text-sekundaer">
                        <?php
                        $namen = array_filter(array_map(static fn(int $gid) => $gruppenNamen[$gid] ?? null, $zuordnungen[$id] ?? []));
                        echo $namen ? Helpers::e(implode(', ', $namen)) : 'keine Gruppen zugewiesen';
                        ?>
                    </div>
                <?php endif; ?>
            </td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($b['letzter_login'])) ?: '–' ?></td>
            <td>
                <?php if ($gesperrt): ?>
                    <span class="badge badge--gesperrt">Gesperrt bis <?= Helpers::e(Helpers::formatDatum($b['gesperrt_bis'], 'H:i')) ?> Uhr</span>
                <?php else: ?>
                    <span class="text-klein text-sekundaer">aktiv</span>
                <?php endif; ?>
            </td>
            <td class="aktionen">
                <a href="/benutzer_bearbeiten.php?id=<?= $id ?>" class="btn btn--klein">Bearbeiten</a>
                <?php if ($gesperrt): ?>
                    <form method="post" action="/benutzer.php">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="entsperren">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn--klein">Jetzt entsperren</button>
                    </form>
                <?php endif; ?>
                <?php if ($id !== (int) $aktuellerBenutzer['id']): ?>
                    <form method="post" action="/benutzer.php" data-bestaetigen="Benutzer „<?= Helpers::e($b['benutzername']) ?>“ wirklich löschen?">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="loeschen">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="karte mt-l" style="max-width:820px;">
    <h3>Neuen Benutzer anlegen</h3>
    <form method="post" action="/benutzer.php" autocomplete="off">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="anlegen">
        <div class="feldreihe">
            <div class="feld">
                <label for="benutzername">Benutzername</label>
                <input type="text" id="benutzername" name="benutzername" required minlength="3" maxlength="50">
            </div>
            <div class="feld">
                <label for="echter_name">Echter Name</label>
                <input type="text" id="echter_name" name="echter_name" maxlength="100">
            </div>
            <div class="feld">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email">
            </div>
        </div>
        <div class="feldreihe">
            <div class="feld">
                <label for="passwort">Passwort</label>
                <input type="password" id="passwort" name="passwort" required minlength="8" autocomplete="new-password">
            </div>
            <div class="feld">
                <label for="passwort_wiederholen">Passwort wiederholen</label>
                <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8" autocomplete="new-password">
            </div>
            <div class="feld">
                <label for="rolle">Rolle</label>
                <select id="rolle" name="rolle" data-rolle-umschalter="#gruppen-auswahl-neu">
                    <option value="eingeschraenkt">Eingeschränkt (nur zugewiesene Gruppen)</option>
                    <option value="admin">Admin (voller Zugriff)</option>
                </select>
            </div>
        </div>
        <?php $ausgewaehlt = []; $auswahlId = 'gruppen-auswahl-neu'; require __DIR__ . '/_gruppen_auswahl.php'; ?>
        <button type="submit" class="btn btn--primaer">Benutzer anlegen</button>
    </form>
</div>
