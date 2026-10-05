# Release-Checkliste Version 1.1

Zuerst auf Staging durchführen, dann – nach erfolgreicher Prüfung – auf Live.

---

## Vorbereitung (Live-System)

- [ ] Backup mit Bildern erstellen: **Backup** → „Backup erstellen" → Datei herunterladen.
- [ ] Backup-Datei lokal aufbewahren, bis das Update auf Staging und Live erfolgreich abgeschlossen ist.

---

## Staging einrichten

- [ ] Backup in die Staging-Umgebung einspielen: Datenbankdatei nach `data/kv_*.sqlite`,
  Bilder nach `bilder/`.
- [ ] Staging gegen die Öffentlichkeit schützen (HTTP-Grundschutz via `.htaccess`/`.htpasswd`
  und `X-Robots-Tag: noindex` in der Serverkonfiguration oder `.htaccess`).

---

## Dateien hochladen (Staging)

- [ ] Alle Dateien der Version 1.1 auf den Staging-Webspace hochladen und vorhandene
  Dateien überschreiben lassen.
- [ ] Im FTP-Programm **versteckte Dateien anzeigen**, damit die `.htaccess`-Dateien
  (insbesondere in `data/`, `bilder/`, `backups/`, `src/`) mitkommen.

---

## Erster Aufruf (Staging)

- [ ] Eine beliebige Seite im Browser aufrufen.
- [ ] Kein Fehler erscheint; die Anwendung öffnet die Login-Seite oder eine bekannte Seite.
- [ ] Unter **System** prüfen:
  - Anwendungsversion: `1.1.0`
  - Schema-Version (DB user_version): `8 / 8`
  - In `data/` liegt eine Datei `pre-migration-*-to-8-*.sqlite` (Snapshot vor der Migration).

---

## Funktionsprüfung (Staging)

### Login und Rollen
- [ ] Admin-Login funktioniert.
- [ ] Eingeschränkter Benutzer sieht nur „Gruppen" und das Benutzermenü – keine Admin-Menüpunkte.

### Import/Export-Rundlauf
- [ ] Gesamtexport durchführen (Excel + Bilder-ZIP herunterladen).
- [ ] Excel-Datei ohne Änderungen erneut importieren.
- [ ] Import-Vorschau zeigt **null Änderungen** (keine „neu", keine „geändert").

### Papierkorb
- [ ] Ein Werk in den Papierkorb verschieben: es verschwindet aus der Werkliste.
- [ ] Werk aus dem Papierkorb wiederherstellen: es erscheint wieder in seinen Gruppen.
- [ ] Werk endgültig löschen (Bestätigung mit „LÖSCHEN").
- [ ] Nach endgültigem Löschen: Import der unveränderten Excel legt das Werk nicht neu an.

### Mehrere Bilder
- [ ] An einem Werk: 3 Bilder hochladen, Reihenfolge ändern, erstes Bild wird Hauptbild.
- [ ] In der Werkliste: Badge „+2" erscheint neben dem Thumbnail.
- [ ] Detailansicht zeigt Galerie-Navigation zwischen den Bildern.

### Web-Veröffentlichung
- [ ] Eine Gruppe veröffentlichen (Link generieren, Felder wählen, mindestens ein Werk freigeben).
- [ ] Link in einem privaten Browserfenster öffnen:
  - Galerie lädt, nur freigegebene Felder sichtbar.
  - Kein Session-Cookie gesetzt (DevTools → Application → Cookies).
  - Response-Header enthalten `X-Robots-Tag: noindex, nofollow, noarchive`.
- [ ] Passwort setzen: Galerie-Link zeigt Passwort-Eingabefeld; nach korrektem Passwort
  erscheint die Galerie.
- [ ] Ablaufdatum auf „gestern" setzen (manueller Hex-Wert in DB nicht nötig – Formular
  mit kurzem Datum): Galerie zeigt neutrale 404-Seite.
- [ ] „Link erneuern": alter Link liefert 404, neuer Link öffnet die Galerie.
- [ ] Gruppe zurückziehen: Link liefert sofort 404.

### Erscheinungsbild
- [ ] Look auf „Archiv" stellen: Seite lädt mit anderem Design, Hell/Dunkel weiterhin
  umschaltbar.
- [ ] Akzentfarbe setzen: Buttons und Akzente zeigen die neue Farbe.
- [ ] Logo hochladen (PNG oder SVG): erscheint in der Kopfleiste und auf der Login-Seite.
- [ ] Logo entfernen: App-Name erscheint wieder als Text.

### Rechtliche Angaben
- [ ] Impressum-Text eintragen (kein URL): `/w/impressum` zeigt den Text.
- [ ] Datenschutz-URL eintragen: Footer-Link leitet auf externe URL weiter.
- [ ] Beide Felder leeren: Footer-Links verschwinden.

### Sicherheitsprüfung
- [ ] Unter **System → Sicherheitsprüfung**: alle geschützten Ordner als „geschützt" markiert.
- [ ] Direktaufruf `https://staging.domain/data/` liefert Fehler, keine Verzeichnisliste.

---

## Live-Deployment

- [ ] Erneutes Backup auf dem Live-System erstellen.
- [ ] Alle Dateien auf den Live-Webspace hochladen (wie Staging).
- [ ] Ersten Seitenaufruf durchführen; System-Seite prüfen (Version 1.1.0, DB-Version 8/8,
  Snapshot-Datei vorhanden).
- [ ] Schritte „Login und Rollen" und „Sicherheitsprüfung" auf Live wiederholen.
- [ ] Einen kurzen Import/Export-Rundlauf auf Live durchführen.

---

## Nach dem Deployment

- [ ] Staging-Backup-Kopie löschen (enthält echte Passwort-Hashes).
- [ ] Unter **System** veraltete Dateien aufräumen, falls der Hinweis erscheint.
- [ ] Snapshot-Dateien `data/pre-migration-*` können nach erfolgreicher Prüfung behalten
  oder manuell entfernt werden (werden bei künftigen Migrationen automatisch auf 3 begrenzt).

---

## Rollback (falls nötig)

1. v1.0-Dateien erneut hochladen (Datenbankschema ist rückwärtskompatibel –
   v1.0-Code läuft auf einem v1.1-Schema).
2. Falls auch die Daten zurückgesetzt werden sollen: Snapshot-Datei
   `data/pre-migration-*-to-8-*.sqlite` als neue Datenbankdatei einsetzen
   (umbenennen auf `kv_*.sqlite`, alte DB löschen) oder das Backup-ZIP
   gemäß `LIESMICH.txt` im Archiv einspielen.
