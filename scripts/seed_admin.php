<?php
declare(strict_types=1);

// CLI-Skript: legt den ersten Admin-Benutzer an (löst das Henne-Ei-Problem,
// da die Benutzerverwaltung selbst einen eingeloggten Admin voraussetzt).
//
// Aufruf: php scripts/seed_admin.php <benutzername> <passwort> [echter_name] [email]

require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/Database.php';

use App\Database;

if ($argc < 3) {
    fwrite(STDERR, "Aufruf: php scripts/seed_admin.php <benutzername> <passwort> [echter_name] [email]\n");
    exit(1);
}

$benutzername = $argv[1];
$passwort = $argv[2];
$echterName = $argv[3] ?? $benutzername;
$email = $argv[4] ?? '';

if (mb_strlen($passwort) < 8) {
    fwrite(STDERR, "Das Passwort muss mindestens 8 Zeichen lang sein.\n");
    exit(1);
}

$pdo = Database::get();

$stmt = $pdo->prepare('SELECT id FROM benutzer WHERE benutzername = :benutzername');
$stmt->execute(['benutzername' => $benutzername]);
if ($stmt->fetch() !== false) {
    fwrite(STDERR, "Ein Benutzer mit diesem Namen existiert bereits.\n");
    exit(1);
}

$hash = password_hash($passwort, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO benutzer (benutzername, echter_name, email, passwort_hash, rolle)
     VALUES (:benutzername, :echter_name, :email, :hash, \'admin\')'
);
$stmt->execute([
    'benutzername' => $benutzername,
    'echter_name' => $echterName,
    'email' => $email,
    'hash' => $hash,
]);

echo "Admin-Benutzer '{$benutzername}' wurde angelegt.\n";
