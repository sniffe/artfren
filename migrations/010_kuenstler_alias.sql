-- Migration 010: Künstler bereinigen.

-- Abweichende Schreibweisen eines Künstlers (normalisiert, klein) → kanonischer
-- Name. Wird beim Import angewandt, damit zusammengeführte Künstler beim
-- Re-Import nicht wieder als neue Werke auftauchen (wie ort_alias für Orte).
CREATE TABLE maler_alias (
    alias  TEXT PRIMARY KEY,
    ziel   TEXT NOT NULL
);
