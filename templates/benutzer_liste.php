<?php
/** @var array $benutzerListe */
/** @var array $gruppen */
/** @var array $benutzerGruppenZuordnung */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Benutzerverwaltung</h2>

<table class="liste">
    <thead>
        <tr>
            <th>Benutzername</th>
            <th>Echter Name</th>
            <th>E-Mail</th>
            <th>Rolle</th>
            <th>Letzter Login</th>
            <th>Status</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($benutzerListe as $b): ?>
        <?php
        $utc = new DateTimeZone('UTC');
        $gesperrt = !empty($b['gesperrt_bis']) && new DateTimeImmutable($b['gesperrt_bis'], $utc) > new DateTimeImmutable('now', $utc);
        ?>
        <tr>
            <td><?= Helpers::e($b['benutzername']) ?></td>
            <td><?= Helpers::e($b['echter_name']) ?></td>
            <td><?= Helpers::e($b['email']) ?></td>
            <td>
                <span class="badge <?= $b['rolle'] === 'admin' ? 'badge--admin' : '' ?>">
                    <?= $b['rolle'] === 'admin' ? 'Admin' : 'Eingeschränkt' ?>
                </span>
                <?php if ($b['rolle'] === 'eingeschraenkt'): ?>
                    <div class="text-klein text-sekundaer">
                        <?php
                        $ids = $benutzerGruppenZuordnung[(int) $b['id']] ?? [];
                        $namen = array_filter($gruppen, fn($g) => in_array((int) $g['id'], $ids, true));
                        echo $namen ? Helpers::e(implode(', ', array_column($namen, 'name'))) : 'keine Gruppen zugewiesen';
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
                <?php if ($gesperrt): ?>
                <form method="post" action="/benutzer.php">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="entsperren">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn--klein">Jetzt entsperren</button>
                </form>
                <?php endif; ?>
                <?php if ((int) $b['id'] !== (int) $aktuellerBenutzer['id']): ?>
                <form method="post" action="/benutzer.php" onsubmit="return confirm('Benutzer „<?= Helpers::e($b['benutzername']) ?>“ wirklich löschen?');">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="loeschen">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="karte mt-l">
    <h3>Neuen Benutzer anlegen</h3>
    <form method="post" action="/benutzer.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="anlegen">
        <div class="feldreihe">
            <div class="feld">
                <label for="benutzername">Benutzername</label>
                <input type="text" id="benutzername" name="benutzername" required>
            </div>
            <div class="feld">
                <label for="echter_name">Echter Name</label>
                <input type="text" id="echter_name" name="echter_name">
            </div>
            <div class="feld">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email">
            </div>
        </div>
        <div class="feldreihe">
            <div class="feld">
                <label for="passwort">Passwort</label>
                <input type="password" id="passwort" name="passwort" required minlength="8">
            </div>
            <div class="feld">
                <label for="rolle">Rolle</label>
                <select id="rolle" name="rolle" onchange="document.getElementById('gruppen-auswahl').style.display = this.value === 'eingeschraenkt' ? 'block' : 'none';">
                    <option value="eingeschraenkt">Eingeschränkt</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="feld" id="gruppen-auswahl">
            <label>Sichtbare Gruppen (nur für eingeschränkte Benutzer)</label>
            <?php if (!$gruppen): ?>
                <p class="text-klein text-sekundaer">Es sind noch keine Gruppen angelegt.</p>
            <?php else: ?>
                <?php foreach ($gruppen as $g): ?>
                    <label style="display:inline-flex;align-items:center;gap:4px;margin-right:12px;font-weight:normal;">
                        <input type="checkbox" name="gruppen[]" value="<?= (int) $g['id'] ?>"> <?= Helpers::e($g['name']) ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn--primaer mt-m">Benutzer anlegen</button>
    </form>
</div>
