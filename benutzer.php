<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireAdmin();
$pdo = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'anlegen') {
        $benutzername = trim((string) ($_POST['benutzername'] ?? ''));
        $echterName = trim((string) ($_POST['echter_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $passwort = (string) ($_POST['passwort'] ?? '');
        $rolle = ($_POST['rolle'] ?? '') === 'admin' ? 'admin' : 'eingeschraenkt';
        $gruppenIds = array_map('intval', $_POST['gruppen'] ?? []);

        if ($benutzername === '' || mb_strlen($passwort) < 8) {
            Helpers::flashSet('fehler', 'Benutzername ist erforderlich, Passwort muss mindestens 8 Zeichen lang sein.');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM benutzer WHERE benutzername = :b');
            $stmt->execute(['b' => $benutzername]);
            if ($stmt->fetch() !== false) {
                Helpers::flashSet('fehler', 'Dieser Benutzername ist bereits vergeben.');
            } else {
                $pdo->beginTransaction();
                $ins = $pdo->prepare(
                    'INSERT INTO benutzer (benutzername, echter_name, email, passwort_hash, rolle)
                     VALUES (:b, :n, :e, :h, :r)'
                );
                $ins->execute([
                    'b' => $benutzername,
                    'n' => $echterName,
                    'e' => $email,
                    'h' => password_hash($passwort, PASSWORD_BCRYPT),
                    'r' => $rolle,
                ]);
                $neueId = (int) $pdo->lastInsertId();

                if ($rolle === 'eingeschraenkt' && $gruppenIds !== []) {
                    $zuord = $pdo->prepare('INSERT INTO benutzer_gruppe (benutzer_id, gruppe_id) VALUES (:u, :g)');
                    foreach ($gruppenIds as $gid) {
                        $zuord->execute(['u' => $neueId, 'g' => $gid]);
                    }
                }
                $pdo->commit();
                Helpers::flashSet('erfolg', "Benutzer „{$benutzername}“ wurde angelegt.");
            }
        }
    } elseif ($aktion === 'loeschen') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $aktuellerBenutzer['id']) {
            Helpers::flashSet('fehler', 'Der eigene Benutzer kann nicht gelöscht werden.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM benutzer WHERE id = :id');
            $stmt->execute(['id' => $id]);
            Helpers::flashSet('erfolg', 'Benutzer wurde gelöscht.');
        }
    } elseif ($aktion === 'entsperren') {
        $id = (int) ($_POST['id'] ?? 0);
        Auth::unlockUser($id);
        Helpers::flashSet('erfolg', 'Benutzer wurde entsperrt.');
    }

    Helpers::redirect('/benutzer.php');
}

$benutzerListe = $pdo->query('SELECT * FROM benutzer ORDER BY benutzername')->fetchAll();
$gruppen = $pdo->query('SELECT id, name FROM gruppen ORDER BY name')->fetchAll();

$benutzerGruppenZuordnung = [];
foreach ($pdo->query('SELECT benutzer_id, gruppe_id FROM benutzer_gruppe') as $z) {
    $benutzerGruppenZuordnung[(int) $z['benutzer_id']][] = (int) $z['gruppe_id'];
}

render('benutzer_liste', [
    'titel' => 'Benutzerverwaltung',
    'aktuelleSeite' => 'benutzer',
    'benutzerListe' => $benutzerListe,
    'gruppen' => $gruppen,
    'benutzerGruppenZuordnung' => $benutzerGruppenZuordnung,
]);
