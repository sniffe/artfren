<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;

Auth::requireAdmin();
$pdo = Database::get();

// Werke ohne Bild
$ohnebildStmt = $pdo->query(
    "SELECT id, ort, maler, titel FROM kunstwerke
     WHERE NOT EXISTS (SELECT 1 FROM bilder b WHERE b.kunstwerk_id = kunstwerke.id)
     ORDER BY ort, maler, titel LIMIT 200"
);
$werkOhneBild = $ohnebildStmt->fetchAll();
$anzahlOhneBild = (int) $pdo->query(
    "SELECT COUNT(*) FROM kunstwerke
     WHERE NOT EXISTS (SELECT 1 FROM bilder b WHERE b.kunstwerk_id = kunstwerke.id)"
)->fetchColumn();

// Werke ohne öffentliche Beschreibung
$ohneBeschrStmt = $pdo->query(
    "SELECT id, ort, maler, titel FROM kunstwerke
     WHERE (beschreibung_oeffentlich IS NULL OR beschreibung_oeffentlich = '')
     ORDER BY ort, maler, titel LIMIT 200"
);
$werkOhneBeschr = $ohneBeschrStmt->fetchAll();
$anzahlOhneBeschr = (int) $pdo->query(
    "SELECT COUNT(*) FROM kunstwerke
     WHERE (beschreibung_oeffentlich IS NULL OR beschreibung_oeffentlich = '')"
)->fetchColumn();

// Werke ohne Ort oder Maler
$unvollstaendigStmt = $pdo->query(
    "SELECT id, ort, maler, titel FROM kunstwerke
     WHERE (ort IS NULL OR ort = '') OR (maler IS NULL OR maler = '')
     ORDER BY ort, maler, titel LIMIT 200"
);
$werkUnvollstaendig = $unvollstaendigStmt->fetchAll();
$anzahlUnvollstaendig = (int) $pdo->query(
    "SELECT COUNT(*) FROM kunstwerke
     WHERE (ort IS NULL OR ort = '') OR (maler IS NULL OR maler = '')"
)->fetchColumn();

// Mögliche Duplikate: gleiche Ort+Maler+Titel-Kombination
$duplikateStmt = $pdo->query(
    "SELECT ort, maler, titel, COUNT(*) AS anzahl
     FROM kunstwerke
     WHERE ort IS NOT NULL AND maler IS NOT NULL AND titel IS NOT NULL
       AND ort != '' AND maler != '' AND titel != ''
     GROUP BY ort, maler, titel
     HAVING COUNT(*) > 1
     ORDER BY anzahl DESC, ort, maler, titel
     LIMIT 100"
);
$duplikate = $duplikateStmt->fetchAll();

render('datenpruefung', [
    'titel' => 'Datenprüfung',
    'aktuelleSeite' => 'datenpruefung',
    'werkOhneBild' => $werkOhneBild,
    'anzahlOhneBild' => $anzahlOhneBild,
    'werkOhneBeschr' => $werkOhneBeschr,
    'anzahlOhneBeschr' => $anzahlOhneBeschr,
    'werkUnvollstaendig' => $werkUnvollstaendig,
    'anzahlUnvollstaendig' => $anzahlUnvollstaendig,
    'duplikate' => $duplikate,
]);
