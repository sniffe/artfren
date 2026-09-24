<?php
declare(strict_types=1);

// CLI-Alternative zum Web-Installer: legt die Datenbank an bzw. bringt sie
// auf den aktuellen Stand. Aufruf: php scripts/migrate.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config.php';
require APP_ROOT . '/vendor/autoload.php';

$angewandt = App\Migration::aktualisiere(App\Database::oeffne());
echo "Datenbank " . DB_PATH . ": {$angewandt} Migration(en) angewandt, Version " . App\Migration::zielVersion() . PHP_EOL;
