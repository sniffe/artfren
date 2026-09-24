-- Migration 001: Basisschema (sieben Tabellen gemäß technischem Konzept).

CREATE TABLE IF NOT EXISTS kunstwerke (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    ort              TEXT,
    maler            TEXT,
    titel            TEXT,
    format           TEXT,
    technik          TEXT,
    entstehungsjahr  INTEGER,
    ankaufjahr       INTEGER,
    ankauf           TEXT,
    ankaufswert      REAL,
    wert             REAL,
    werktyp          TEXT NOT NULL DEFAULT 'Bild' CHECK (werktyp IN ('Bild', 'Objekt')),
    status_farbe     TEXT CHECK (status_farbe IN ('rot', 'gruen', 'orange') OR status_farbe IS NULL),
    erstellt_am      TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_kunstwerke_ort ON kunstwerke(ort);
CREATE INDEX IF NOT EXISTS idx_kunstwerke_maler ON kunstwerke(maler);
CREATE INDEX IF NOT EXISTS idx_kunstwerke_titel ON kunstwerke(titel);
-- Re-Import-Abgleichsschlüssel (siehe Konzept, Abschnitt Datenimport)
CREATE INDEX IF NOT EXISTS idx_kunstwerke_abgleich ON kunstwerke(ort, maler, titel);

CREATE TABLE IF NOT EXISTS bilder (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    kunstwerk_id  INTEGER NOT NULL REFERENCES kunstwerke(id) ON DELETE CASCADE,
    dateiname     TEXT NOT NULL,
    ist_hauptbild INTEGER NOT NULL DEFAULT 1,
    sortierung    INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_bilder_kunstwerk ON bilder(kunstwerk_id);

CREATE TABLE IF NOT EXISTS benutzer (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    benutzername   TEXT NOT NULL UNIQUE,
    echter_name    TEXT,
    email          TEXT,
    passwort_hash  TEXT NOT NULL,
    rolle          TEXT NOT NULL DEFAULT 'eingeschraenkt' CHECK (rolle IN ('admin', 'eingeschraenkt')),
    letzter_login  TEXT,
    gesperrt_bis   TEXT,
    fehlversuche   INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS gruppen (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,
    erstellt_von  INTEGER REFERENCES benutzer(id) ON DELETE SET NULL,
    erstellt_am   TEXT NOT NULL DEFAULT (datetime('now')),
    geaendert_am  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS gruppe_kunstwerk (
    gruppe_id     INTEGER NOT NULL REFERENCES gruppen(id) ON DELETE CASCADE,
    kunstwerk_id  INTEGER NOT NULL REFERENCES kunstwerke(id) ON DELETE CASCADE,
    PRIMARY KEY (gruppe_id, kunstwerk_id)
);

CREATE INDEX IF NOT EXISTS idx_gk_kunstwerk ON gruppe_kunstwerk(kunstwerk_id);

CREATE TABLE IF NOT EXISTS benutzer_gruppe (
    benutzer_id  INTEGER NOT NULL REFERENCES benutzer(id) ON DELETE CASCADE,
    gruppe_id    INTEGER NOT NULL REFERENCES gruppen(id) ON DELETE CASCADE,
    PRIMARY KEY (benutzer_id, gruppe_id)
);

CREATE TABLE IF NOT EXISTS backups (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    dateiname      TEXT NOT NULL,
    erstellt_am    TEXT NOT NULL DEFAULT (datetime('now')),
    erstellt_von   INTEGER REFERENCES benutzer(id) ON DELETE SET NULL,
    dateigroesse   INTEGER NOT NULL DEFAULT 0
);
