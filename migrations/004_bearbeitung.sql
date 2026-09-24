-- Migration 004: Werke im Programm bearbeiten.

-- Zeitpunkt der letzten manuellen Änderung. Der Import überschreibt solche
-- Werke nur nach ausdrücklicher Bestätigung.
ALTER TABLE kunstwerke ADD COLUMN bearbeitet_am TEXT;

-- Wird Ort, Maler oder Titel im Programm geändert, bleibt der alte
-- Abgleichsschlüssel hier erhalten: ein Import der unveränderten Tabelle
-- erkennt das Werk so wieder, statt es doppelt anzulegen.
CREATE TABLE werk_schluessel_alias (
    schluessel    TEXT NOT NULL,
    kunstwerk_id  INTEGER NOT NULL REFERENCES kunstwerke(id) ON DELETE CASCADE,
    PRIMARY KEY (schluessel, kunstwerk_id)
);
