<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireLogin();
$istAdmin = Auth::isAdmin($aktuellerBenutzer);
$pdo = Database::get();

function pruefe_gruppen_zugriff(array $benutzer, bool $istAdmin, int $gruppeId): void
{
    if ($istAdmin) {
        return;
    }
    $sichtbar = Auth::sichtbareGruppenIds($benutzer);
    if (!in_array($gruppeId, $sichtbar, true)) {
        http_response_code(403);
        exit('Zugriff verweigert: diese Gruppe ist dir nicht zugewiesen.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$istAdmin) {
        http_response_code(403);
        exit('Zugriff verweigert.');
    }
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'mitglieder_speichern') {
        $gruppeId = (int) ($_POST['gruppe_id'] ?? 0);
        $werkIds = array_map('intval', $_POST['werk_ids'] ?? []);

        $pdo->beginTransaction();
        $del = $pdo->prepare('DELETE FROM gruppe_kunstwerk WHERE gruppe_id = :g');
        $del->execute(['g' => $gruppeId]);

        $ins = $pdo->prepare('INSERT INTO gruppe_kunstwerk (gruppe_id, kunstwerk_id) VALUES (:g, :k)');
        foreach ($werkIds as $wid) {
            $ins->execute(['g' => $gruppeId, 'k' => $wid]);
        }
        $upd = $pdo->prepare("UPDATE gruppen SET geaendert_am = datetime('now') WHERE id = :id");
        $upd->execute(['id' => $gruppeId]);
        $pdo->commit();

        Helpers::flashSet('erfolg', 'Mitglieder der Gruppe wurden aktualisiert (' . count($werkIds) . ' Werke).');
        Helpers::redirect('/gruppe.php?id=' . $gruppeId . '&mitglieder_gespeichert=1');
    }
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM gruppen WHERE id = :id');
$stmt->execute(['id' => $id]);
$gruppe = $stmt->fetch();

if ($gruppe === false) {
    http_response_code(404);
    exit('Gruppe wurde nicht gefunden.');
}

pruefe_gruppen_zugriff($aktuellerBenutzer, $istAdmin, $id);

$werke = $pdo->prepare(
    "SELECT k.*, (
        SELECT dateiname FROM bilder b WHERE b.kunstwerk_id = k.id
        ORDER BY ist_hauptbild DESC, sortierung ASC LIMIT 1
    ) AS bild_dateiname
    FROM kunstwerke k
    JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
    WHERE gk.gruppe_id = :id
    ORDER BY k.ort, k.maler, k.titel"
);
$werke->execute(['id' => $id]);

render('gruppe_ansicht', [
    'titel' => $gruppe['name'],
    'aktuelleSeite' => 'gruppen',
    'breit' => true,
    'gruppe' => $gruppe,
    'werke' => $werke->fetchAll(),
    'istAdmin' => $istAdmin,
    'geradeErstellt' => isset($_GET['neu']),
    'geradeGespeichert' => isset($_GET['mitglieder_gespeichert']),
]);
