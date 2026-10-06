-- Migration 009: Sprache je Benutzer (Paket 1a), Export-Profile (Paket 6),
-- Export-Rechte je Benutzer (Paket 6a).
-- Ausschließlich additiv – keine Spalten umbenannt oder gelöscht.

-- Paket 1a: Sprache je Benutzer (NULL = Standardsprache der Anwendung)
ALTER TABLE benutzer ADD COLUMN sprache TEXT;

-- Paket 6: Export-Profile
CREATE TABLE IF NOT EXISTS export_profile (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT    NOT NULL,
    felder              TEXT    NOT NULL DEFAULT '',
    bild                INTEGER NOT NULL DEFAULT 1,
    titelblock          INTEGER NOT NULL DEFAULT 1,
    leer_ausblenden     INTEGER NOT NULL DEFAULT 1,
    sprache             TEXT,
    ist_standard        INTEGER NOT NULL DEFAULT 0,
    fuer_eingeschraenkte INTEGER NOT NULL DEFAULT 0,
    layout              TEXT    NOT NULL DEFAULT 'einzelblatt',
    summe               INTEGER NOT NULL DEFAULT 1,
    erstellt_von        INTEGER,
    erstellt_am         TEXT    NOT NULL DEFAULT (datetime('now')),
    geaendert_am        TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (erstellt_von) REFERENCES benutzer(id) ON DELETE SET NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uidx_export_profile_name
    ON export_profile(name COLLATE NOCASE);

-- Paket 6a: Export-Rechte je Benutzer (Admins werden systemseitig ignoriert)
ALTER TABLE benutzer ADD COLUMN darf_excel          INTEGER NOT NULL DEFAULT 0;
ALTER TABLE benutzer ADD COLUMN darf_bilder_export  INTEGER NOT NULL DEFAULT 0;

PRAGMA user_version = 9;
