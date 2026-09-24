-- Migration 003: Als Status gelten nur die Farben der Excel-Legende
-- (Rot, Orange, Grün). Gelbe/blaue Markierungen sind ohne Bedeutung.
UPDATE kunstwerke SET status_farbe = NULL WHERE status_farbe NOT IN ('rot', 'orange', 'gruen');
