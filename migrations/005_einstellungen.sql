-- Migration 005: Einstellungen, die im Browser geändert werden (System →
-- Einstellungen). Sie liegen in der Datenbank statt in src/config.php, damit
-- ein Update sie nie überschreibt – und sie sind automatisch im Backup.
CREATE TABLE einstellungen (
    schluessel    TEXT PRIMARY KEY,
    wert          TEXT NOT NULL,
    geaendert_am  TEXT NOT NULL DEFAULT (datetime('now'))
);
