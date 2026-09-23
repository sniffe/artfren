<?php
declare(strict_types=1);

// CLI-Skript: legt die SQLite-Datenbank an bzw. bringt sie auf den
// aktuellen Stand. Aufruf: php scripts/migrate.php

require __DIR__ . '/../src/config.php';

$dataDir = dirname(DB_PATH);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schema = file_get_contents(__DIR__ . '/../migrations/schema.sql');
$pdo->exec($schema);

echo "Datenbank aktualisiert: " . DB_PATH . PHP_EOL;
