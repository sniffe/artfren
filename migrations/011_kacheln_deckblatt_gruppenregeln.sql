-- Migration 011: Kachellayout, Deckblatt/Fußzeile im PDF, Sortier- und Filterregeln pro Gruppe.

-- PDF-Export-Profile (Punkte 44 und 45)
ALTER TABLE export_profile ADD COLUMN kachel_raster TEXT NOT NULL DEFAULT '2x2'; -- '2x2' (A4 hoch) | '3x2' (A4 quer)
ALTER TABLE export_profile ADD COLUMN deckblatt     INTEGER NOT NULL DEFAULT 0;
ALTER TABLE export_profile ADD COLUMN fusszeile     INTEGER NOT NULL DEFAULT 0;

-- Gruppen (Punkt 46); NULL = bisheriges Verhalten (Sortierung von Hand, keine Filter angeboten)
ALTER TABLE gruppen ADD COLUMN sortierung_regel TEXT; -- JSON, z. B. {"modus":"felder","felder":[{"feld":"maler","richtung":"asc"}]}
ALTER TABLE gruppen ADD COLUMN filter_angebot   TEXT; -- JSON, angebotene Filter, z. B. ["maler","technik","entstehungsjahr"]
