-- Paket 5: Web-Veröffentlichung von Gruppen

-- Web-Einstellungen je Gruppe
ALTER TABLE gruppen ADD COLUMN web_aktiv INTEGER NOT NULL DEFAULT 0;
ALTER TABLE gruppen ADD COLUMN web_token TEXT;
ALTER TABLE gruppen ADD COLUMN web_titel TEXT;
ALTER TABLE gruppen ADD COLUMN web_einleitung TEXT;
ALTER TABLE gruppen ADD COLUMN web_felder TEXT; -- JSON-Array der sichtbaren Feldschlüssel
ALTER TABLE gruppen ADD COLUMN web_bilder_modus TEXT NOT NULL DEFAULT 'haupt'; -- 'alle' | 'haupt'
ALTER TABLE gruppen ADD COLUMN web_passwort_hash TEXT;
ALTER TABLE gruppen ADD COLUMN web_ablauf TEXT; -- UTC datetime oder NULL (kein Ablauf)
ALTER TABLE gruppen ADD COLUMN web_einbetten_von TEXT; -- erlaubter Origin oder NULL
ALTER TABLE gruppen ADD COLUMN web_look TEXT;
ALTER TABLE gruppen ADD COLUMN web_aufrufe INTEGER NOT NULL DEFAULT 0;
ALTER TABLE gruppen ADD COLUMN web_letzter_aufruf TEXT;
ALTER TABLE gruppen ADD COLUMN web_veroeffentlicht_am TEXT;

CREATE UNIQUE INDEX IF NOT EXISTS idx_gruppen_web_token ON gruppen (web_token) WHERE web_token IS NOT NULL;

-- Reihenfolge der Werke innerhalb einer Gruppe
ALTER TABLE gruppe_kunstwerk ADD COLUMN sortierung INTEGER NOT NULL DEFAULT 0;

CREATE INDEX IF NOT EXISTS idx_gruppe_kunstwerk_sort ON gruppe_kunstwerk (gruppe_id, sortierung);

-- Passwort-Throttling für öffentliche Galerien (getrennt von login_versuche)
CREATE TABLE IF NOT EXISTS web_versuche (
    id        INTEGER PRIMARY KEY,
    ip        TEXT NOT NULL,
    gruppe_id INTEGER NOT NULL,
    zeitpunkt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_web_versuche_ip_gruppe ON web_versuche (ip, gruppe_id, zeitpunkt);

PRAGMA user_version = 8;
