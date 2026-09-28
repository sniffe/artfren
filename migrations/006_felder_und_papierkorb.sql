-- Neue Felder auf kunstwerke (additiv, kein DROP, kein REBUILD).
-- Paket 1: Inhaltliche Felder; Paket 3 nutzt geloescht_am/geloescht_von.

ALTER TABLE kunstwerke ADD COLUMN beschreibung_oeffentlich TEXT;
ALTER TABLE kunstwerke ADD COLUMN beschreibung_intern TEXT;
ALTER TABLE kunstwerke ADD COLUMN herkunft TEXT;
ALTER TABLE kunstwerke ADD COLUMN copyright TEXT;
ALTER TABLE kunstwerke ADD COLUMN web_freigabe INTEGER NOT NULL DEFAULT 0;

-- Soft-Delete (Paket 3 implementiert die Logik, die Spalten werden jetzt angelegt).
ALTER TABLE kunstwerke ADD COLUMN geloescht_am TEXT;
ALTER TABLE kunstwerke ADD COLUMN geloescht_von TEXT;

CREATE INDEX IF NOT EXISTS idx_kunstwerke_geloescht_am ON kunstwerke (geloescht_am);

-- Merkt sich endgueltig geloeschte Abgleichsschluessel damit der Import
-- geloeschte Werke nicht neu anlegt (Paket 3).
CREATE TABLE IF NOT EXISTS werk_tombstone (
    id           INTEGER PRIMARY KEY,
    schluessel   TEXT NOT NULL,
    geloescht_am TEXT NOT NULL DEFAULT (datetime('now')),
    geloescht_von_name TEXT
);

PRAGMA user_version = 6;
