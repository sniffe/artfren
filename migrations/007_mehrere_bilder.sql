-- Bildunterschrift (optional, z. B. "Rückseite").
ALTER TABLE bilder ADD COLUMN beschriftung TEXT;

-- Beschleunigt Sortierung und Haupt-Bild-Abfragen.
CREATE INDEX IF NOT EXISTS idx_bilder_kunstwerk_sortierung ON bilder (kunstwerk_id, sortierung);

PRAGMA user_version = 7;
