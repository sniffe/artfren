<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireLogin();
$istAdmin = Auth::isAdmin($aktuellerBenutzer);
$pdo = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$istAdmin) {
        http_response_code(403);
        exit('Zugriff verweigert.');
    }
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'anlegen') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $werkIds = array_map('intval', $_POST['werk_ids'] ?? []);

        if ($name === '') {
            Helpers::flashSet('fehler', 'Bitte einen Gruppennamen angeben.');
            Helpers::redirect('/gruppe_neu.php');
        }
        if ($werkIds === []) {
            Helpers::flashSet('fehler', 'Bitte mindestens ein Werk auswählen.');
            Helpers::redirect('/gruppe_neu.php');
        }

        $pdo->beginTransaction();
        $ins = $pdo->prepare('INSERT INTO gruppen (name, erstellt_von) VALUES (:name, :von)');
        $ins->execute(['name' => $name, 'von' => $aktuellerBenutzer['id']]);
        $neueId = (int) $pdo->lastInsertId();

        $zuord = $pdo->prepare('INSERT INTO gruppe_kunstwerk (gruppe_id, kunstwerk_id) VALUES (:g, :k)');
        foreach ($werkIds as $wid) {
            $zuord->execute(['g' => $neueId, 'k' => $wid]);
        }
        $pdo->commit();

        Helpers::flashSet('erfolg', "Gruppe „{$name}“ wurde mit " . count($werkIds) . ' Werken angelegt.');
        Helpers::redirect('/gruppe.php?id=' . $neueId . '&neu=1');
    } elseif ($aktion === 'umbenennen') {
        $id = (int) ($_POST['id'] ?? 0);
        $neuerName = trim((string) ($_POST['name'] ?? ''));
        if ($neuerName !== '') {
            $stmt = $pdo->prepare("UPDATE gruppen SET name = :name, geaendert_am = datetime('now') WHERE id = :id");
            $stmt->execute(['name' => $neuerName, 'id' => $id]);
            Helpers::flashSet('erfolg', 'Gruppenname wurde geändert.');
        }
        Helpers::redirect('/gruppen.php');
    } elseif ($aktion === 'loeschen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM gruppen WHERE id = :id');
        $stmt->execute(['id' => $id]);
        Helpers::flashSet('erfolg', 'Gruppe wurde gelöscht (die enthaltenen Kunstwerke bleiben erhalten).');
        Helpers::redirect('/gruppen.php');
    }
}

if ($istAdmin) {
    $gruppen = $pdo->query(
        "SELECT g.*, (SELECT COUNT(*) FROM gruppe_kunstwerk gk WHERE gk.gruppe_id = g.id) AS anzahl_werke
         FROM gruppen g ORDER BY g.name"
    )->fetchAll();
} else {
    $sichtbar = Auth::sichtbareGruppenIds($aktuellerBenutzer);
    if ($sichtbar === []) {
        $gruppen = [];
    } else {
        $sql = "SELECT g.*, (SELECT COUNT(*) FROM gruppe_kunstwerk gk WHERE gk.gruppe_id = g.id) AS anzahl_werke
                FROM gruppen g WHERE g.id IN (" . Helpers::platzhalter($sichtbar) . ") ORDER BY g.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($sichtbar);
        $gruppen = $stmt->fetchAll();
    }
}

render('gruppen_liste', [
    'titel' => 'Gruppen',
    'aktuelleSeite' => 'gruppen',
    'gruppen' => $gruppen,
    'istAdmin' => $istAdmin,
]);
