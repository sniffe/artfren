<?php
declare(strict_types=1);

// CLI-Alternative zum Web-Installer: legt einen Admin-Benutzer an.
// Aufruf: php scripts/seed_admin.php <benutzername> <passwort> [echter_name] [email]

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config.php';
require APP_ROOT . '/vendor/autoload.php';

use App\Auth;
use App\BenutzerRepository;
use App\Database;
use App\Migration;

if ($argc < 3) {
    fwrite(STDERR, "Aufruf: php scripts/seed_admin.php <benutzername> <passwort> [echter_name] [email]\n");
    exit(1);
}

[, $benutzername, $passwort] = $argv;
$echterName = $argv[3] ?? $benutzername;
$email = $argv[4] ?? '';

$fehler = BenutzerRepository::benutzernameFehler($benutzername)
    ?? BenutzerRepository::stammdatenFehler($echterName, $email)
    ?? Auth::passwortFehler($passwort, $passwort);
if ($fehler !== null) {
    fwrite(STDERR, $fehler . "\n");
    exit(1);
}

$pdo = Database::oeffne();
Migration::aktualisiere($pdo);
$repo = new BenutzerRepository($pdo);
if ($repo->benutzernameVergeben($benutzername)) {
    fwrite(STDERR, "Ein Benutzer mit diesem Namen existiert bereits.\n");
    exit(1);
}

$repo->anlegen($benutzername, $echterName, $email, $passwort, 'admin', []);
echo "Admin-Benutzer '{$benutzername}' wurde angelegt.\n";
