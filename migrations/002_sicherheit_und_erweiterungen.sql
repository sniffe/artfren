-- Migration 002: Status-Farben erweitern, Login-Drosselung, Sitzungs-Version,
-- Protokoll, Ort-Aliase, Backup-Variante.

-- status_farbe: die Galerie-Tabelle nutzt neben Rot/Grün/Orange auch Gelb und
-- Blau. SQLite kann CHECK-Constraints nicht ändern, daher Tabellen-Neuaufbau
-- (der Migrator schaltet Fremdschlüssel währenddessen ab).
CREATE TABLE kunstwerke_neu (
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
    status_farbe     TEXT CHECK (status_farbe IN ('rot', 'orange', 'gelb', 'gruen', 'blau', 'violett') OR status_farbe IS NULL),
    erstellt_am      TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO kunstwerke_neu (id, ort, maler, titel, format, technik, entstehungsjahr, ankaufjahr, ankauf, ankaufswert, wert, werktyp, status_farbe, erstellt_am)
SELECT id, ort, maler, titel, format, technik, entstehungsjahr, ankaufjahr, ankauf, ankaufswert, wert, werktyp, status_farbe, erstellt_am
FROM kunstwerke;

DROP TABLE kunstwerke;
ALTER TABLE kunstwerke_neu RENAME TO kunstwerke;

CREATE INDEX idx_kunstwerke_ort ON kunstwerke(ort);
CREATE INDEX idx_kunstwerke_maler ON kunstwerke(maler);
CREATE INDEX idx_kunstwerke_titel ON kunstwerke(titel);
CREATE INDEX idx_kunstwerke_abgleich ON kunstwerke(ort, maler, titel);

-- Erhöht sich bei Passwortänderung: alle anderen Sitzungen werden ungültig.
ALTER TABLE benutzer ADD COLUMN sitzung_version INTEGER NOT NULL DEFAULT 1;

CREATE TABLE login_versuche (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ip         TEXT NOT NULL,
    zeitpunkt  TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_login_versuche_ip ON login_versuche(ip, zeitpunkt);

CREATE TABLE protokoll (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    zeitpunkt     TEXT NOT NULL DEFAULT (datetime('now')),
    benutzer_id   INTEGER REFERENCES benutzer(id) ON DELETE SET NULL,
    benutzername  TEXT,
    ip            TEXT,
    aktion        TEXT NOT NULL,
    details       TEXT
);
CREATE INDEX idx_protokoll_zeitpunkt ON protokoll(zeitpunkt);

-- Abweichende Schreibweisen eines Orts (normalisiert, klein) → kanonischer Name.
-- Wird beim Import angewandt, damit zusammengeführte Orte beim Re-Import
-- nicht wieder als neue Werke auftauchen.
CREATE TABLE ort_alias (
    alias  TEXT PRIMARY KEY,
    ziel   TEXT NOT NULL
);

ALTER TABLE backups ADD COLUMN mit_bildern INTEGER NOT NULL DEFAULT 1;
