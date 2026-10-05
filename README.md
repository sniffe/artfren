# Kunstverwaltung

Web-Anwendung (PHP 8.1+, SQLite) zur Verwaltung von Kunstwerken mit frei
definierbaren Gruppen, zwei Nutzerrollen, Export- und Backup-Funktionen.
Umsetzung gemäß dem übergebenen technischen Konzept-Dokument.

Der angezeigte Name der Anwendung ist frei wählbar: als Administrator unter
**System → Einstellungen** ändern (wirkt sich auf Seitentitel, Kopfzeile und
Login-Seite aus). Der Wert liegt in der Datenbank und bleibt bei Updates
erhalten; `APP_NAME` in `src/config.php` ist nur der Standardwert.

## Einrichtung (Shared-Hosting, kein Shell-/SSH-Zugriff nötig)

Kein Composer, kein Terminal, kein Cronjob nötig – die komplette
Installation läuft über den Browser. Der `vendor/`-Ordner mit allen
Abhängigkeiten ist bereits Teil dieses Repositories.

1. Dieses Verzeichnis komplett (inklusive `vendor/`) auf den Webspace
   hochladen, z. B. per FTP oder Datei-Manager des Hosters. Document Root
   zeigt direkt auf dieses Verzeichnis.
2. Im Browser `https://deine-domain.tld/install.php` aufrufen und den
   zwei Schritten folgen:
   - **Datenbank einrichten** (legt die SQLite-Datei mit zufälligem,
     nicht erratbarem Namen und alle Tabellen an),
   - **Administrator anlegen**.
   Zeigt der Installer fehlende Schreibrechte an, müssen `data/`,
   `bilder/` und `backups/` beim Hoster beschreibbar sein (z. B. Rechte 755).
3. Der Installer prüft danach automatisch, ob die geschützten Ordner
   (`data/`, `backups/`, `bilder/`, `src/`) wirklich von außen gesperrt sind.
   Erscheint eine Warnung, wertet der Webserver `.htaccess` nicht aus – dann
   beim Hoster aktivieren lassen, **bevor echte Daten importiert werden**.
   Die Prüfung lässt sich jederzeit unter „System“ wiederholen.
4. `install.php` sperrt sich nach der Einrichtung selbst und kann zusätzlich
   gelöscht werden.
5. Sobald ein SSL-Zertifikat aktiv ist: die Seite über `https://` aufrufen
   und unter **System → Einstellungen** „HTTPS erzwingen“ einschalten.

### Updates einspielen

Werke, Benutzer, Bilder, Backups und Einstellungen bleiben bei einem Update
erhalten: Sie liegen in `data/`, `bilder/` und `backups/` bzw. in der
Datenbank, und eine neue Version bringt in diese Ordner nur ihre
`.htaccess`-Sperre mit.

1. Unter **Backup** ein Backup erstellen und herunterladen (für den Notfall).
2. Die neue Version als ZIP herunterladen (auf GitHub: „Code“ → „Download
   ZIP“ oder `https://github.com/sniffe/artfren/archive/refs/heads/<branch>.zip`)
   und entpacken.
3. Im FTP-Programm **versteckte Dateien anzeigen** einschalten (FileZilla:
   Server → Anzeigen versteckter Dateien erzwingen), damit die
   `.htaccess`-Dateien mitkommen.
4. Den **Inhalt** des entpackten Ordners (nicht den Ordner selbst) in den
   Programmordner auf dem Server hochladen und alles überschreiben lassen.
5. Warten, bis das Hochladen fertig ist (FileZilla: „Zu übertragende
   Dateien“ und „Fehlgeschlagene Übertragungen“ stehen auf 0). `vendor/`
   hat über tausend Dateien und braucht oft 10–30 Minuten.
6. Irgendeine Seite aufrufen und anmelden. Nötige Datenbank-Änderungen
   laufen dabei automatisch (`migrations/NNN_*.sql`, Versionsstand über
   `PRAGMA user_version`).
7. Unter **System** kontrollieren:
   - „Vollständigkeit des Programms“ meldet alle Programmdateien als
     vorhanden,
   - werden **veraltete Dateien** früherer Versionen angezeigt: „Jetzt
     löschen“.
8. Optional: das wieder mitgebrachte `install.php` löschen (es ist gesperrt,
   sobald ein Administrator existiert).

**Einmalig beim Update von einer Version ohne „System → Einstellungen“:** Der Name der
Anwendung wurde früher in `src/config.php` eingetragen; diese Datei wird beim
Update überschrieben. Den Namen daher einmal unter **System → Einstellungen**
eintragen – ab dann bleibt er bei allen Updates erhalten.

#### Wenn nach dem Update etwas nicht stimmt

- **Seite „Das Programm ist noch nicht vollständig hochgeladen“:** Die Seite
  nennt die betroffenen Ordner (geprüft von `upload_pruefung.php`, bevor
  irgendetwas anderes geladen wird). Upload abwarten bzw. diese Ordner erneut
  hochladen und überschreiben. Liegt nach dem Upload alles eine Ebene zu tief
  (z. B. `artfren-…/src`), den Inhalt eine Ebene nach oben verschieben.
- **Englische Meldung „Failed opening required …/src/bootstrap.php“:** Stammt
  von einer älteren Version ohne Upload-Prüfung – der Ordner `src/` fehlt.
  Die aktuelle Version komplett hochladen.
- **Rote Warnung „Es fehlen Schutzdateien (.htaccess)“:** Das FTP-Programm
  hat versteckte Dateien ausgelassen. Schritt 3 und 4 wiederholen, sonst
  können private Ordner von außen abrufbar sein.
- **„Es ist ein Fehler aufgetreten“ mit Fehler-ID:** Die Details stehen in
  `data/logs/php-fehler.log` (per FTP herunterladen und nach der Fehler-ID
  suchen).

Für Entwickler: Vor jeder neuen Version `php scripts/dateiliste.php`
ausführen, damit `src/dateiliste.txt` (Grundlage der
Vollständigkeitsprüfung) aktuell ist.

### Alternative mit Shell-Zugriff (lokale Entwicklung)

`composer install`, dann `php scripts/migrate.php` und
`php scripts/seed_admin.php <benutzername> <passwort>`. Die Skripte sind
nur über die Kommandozeile ausführbar.

## Rollen

- **Administrator:** alles – Werke, Import, Export, Gruppen, Benutzer,
  Backup und System.
- **Eingeschränkt:** sieht nur die Gruppen, die ihm zugewiesen sind, samt
  deren Werken, und kann diese Gruppen als Excel, Bilder-ZIP und PDF
  exportieren. Keine Bearbeitung.

## Import

Die Excel-Datei kann **direkt** hochgeladen werden (erstes Tabellenblatt):
Die Kopfzeile wird automatisch gefunden, Legenden- und Summenzeilen werden
übersprungen, farbig markierte Zeilen werden laut Legende zum Status (Rot,
Orange, Grün); andere Farben werden ignoriert. CSV (UTF-8 oder Windows-1252, `;`/`,`/Tab) funktioniert ebenfalls.

- Beim erneuten Import werden Werke über Ort + Maler + Titel wiedererkannt;
  gleiche Kombinationen (z. B. mehrere „o.T.“) werden der Reihe nach
  zugeordnet. Nur zugeordnete Spalten werden aktualisiert.
- **Orte bereinigen** schlägt Tippvarianten vor („Top 17“/„Tio 17“) und merkt
  sich zusammengeführte Schreibweisen für künftige Importe.
- **Bilder hochladen** (einzeln oder als ZIP): Der Dateiname muss der Spalte
  „Dateiname“ entsprechen. Verknüpfungen werden auch gespeichert, wenn das
  Bild noch fehlt – es erscheint automatisch, sobald es hochgeladen ist.

## Export

Gesamtexport und Gruppenexport liefern jeweils **zwei Dateien**, die
zusammengehören:

- **Excel** mit allen Feldern; die Spalte „Dateiname“ nennt die Bilddatei.
- **Bilder-ZIP** mit genau diesen Dateien (flach, ohne Unterordner). Fehlende
  Dateien stehen in `FEHLENDE_BILDER.txt`.

Beides lässt sich unverändert wieder einspielen: ZIP unter „Bilder hochladen“
(oder per FTP nach `bilder/`), Excel unter „Tabelle importieren“ – in
beliebiger Reihenfolge. Pro Gruppe gibt es zusätzlich das PDF (ein Werk pro
Seite). Gruppen, Benutzer und Einstellungen enthält nur das Backup.

## Backup und Wiederherstellen

Unter **Backup** lassen sich Backups mit oder ohne Bilder erstellen und
herunterladen (ZIP mit Datenbank-Schnappschuss `kunstverwaltung.sqlite`,
ggf. dem Ordner `bilder/` und einer `LIESMICH.txt`). Es gibt keine
automatischen Backups – regelmäßig eines anlegen und herunterladen.

Wiederherstellen per FTP:

1. Die vorhandene Datei `data/kv_*.sqlite` herunterladen (zur Sicherheit)
   und auf dem Server löschen – es darf nur eine `kv_*.sqlite` in `data/`
   liegen.
2. `kunstverwaltung.sqlite` aus dem Backup-ZIP nach `data/` hochladen und in
   `kv_<beliebige Buchstaben>.sqlite` umbenennen.
3. Bei einem Backup mit Bildern den Inhalt von `bilder/` nach `bilder/` auf
   dem Server hochladen.

## Werke bearbeiten

Über „+ Neues Werk anlegen“ in der Werkliste lassen sich Werke auch ohne
Tabelle erfassen (leeres Formular, Bild direkt mit hochladbar).

Admins können jedes Werk aus der Werkliste („bearbeiten“) oder der
Detailansicht („Bearbeiten“) ändern, inklusive Bild (hochladen, aus dem
Bilder-Ordner zuweisen oder Zuordnung entfernen). In der Werkliste öffnet ein
Klick auf den Bild-Platzhalter direkt einen Dialog zum Hochladen/Zuweisen.

Im Programm bearbeitete Werke sind vor dem nächsten Tabellen-Import
geschützt: Die Vorschau listet sie gesondert, überschrieben werden sie nur mit
ausdrücklichem Häkchen. Werden Ort, Maler oder Titel geändert, erkennt der
Import das Werk über den alten Namen trotzdem wieder (kein Duplikat).

## System (nur Administratoren)

- **Einstellungen:** Name der Anwendung, „HTTPS erzwingen“ (nur über eine
  `https://`-Verbindung einschaltbar, damit man sich nicht aussperrt).
- **Vollständigkeit des Programms** und fehlende `.htaccess`-Schutzdateien
  (siehe „Updates einspielen“).
- **Veraltete Dateien** früherer Versionen anzeigen und löschen.
- **Sicherheitsprüfung:** ruft die geschützten Ordner von außen ab und
  meldet, ob sie wirklich gesperrt sind.
- Server-Informationen (PHP-Version, Upload-Grenzen, Datenbankgröße,
  Schema-Version) und das **Protokoll** aller wichtigen Aktionen.

## Sicherheit

- Passwörter mit bcrypt; Kontosperre nach 4 Fehlversuchen (15 Min.) plus
  Drosselung je IP-Adresse (20 Fehlversuche über alle Konten).
- Sitzungen: HttpOnly/SameSite-Cookie, Ablauf nach 60 Min. Inaktivität,
  Passwortänderung meldet alle anderen Geräte ab.
- CSRF-Schutz für alle Formulare, Content-Security-Policy ohne Inline-Skripte.
- Bilder werden nur über `bild.php` mit Rechteprüfung ausgeliefert.
- Exporte sind gegen Formel-Injection in Excel geschützt.
- Schriften lokal eingebunden (keine Datenübertragung an Google).
- Protokoll aller wichtigen Aktionen unter „System“.

## Struktur

- `*.php` im Wurzelverzeichnis – Einstiegspunkte (Controller). Jeder lädt
  zuerst `upload_pruefung.php` (Vollständigkeit, ohne Abhängigkeiten), dann
  `src/bootstrap.php`.
- `src/` – Klassen (PSR-4 `App\`): `Auth`, `Database`, `Migration`,
  Repositories, `TabellenImport`, `Export`, `Bilder`, `BildUpload`, `Backup`,
  `Protokoll`, `Sicherheitscheck`, `OrtBereinigung`, `Einstellungen`
  (im Browser änderbare Werte), `Wartung` (Vollständigkeit, Aufräumen).
  `src/config.php` enthält nur technische Standardwerte,
  `src/dateiliste.txt` die Dateiliste der Version.
- `templates/` – reine Ausgabe-Templates, keine Geschäftslogik/SQL.
- `assets/tokens.css` – alle Design-Tokens; `assets/style.css` verwendet nur
  diese Variablen. `assets/fonts/` – Schriften (SIL Open Font License).
- `migrations/` – nummerierte Schema-Migrationen.
- `data/` – Datenbank, Bild-Cache (`cache/`), Fehlerprotokoll (`logs/`).
- `bilder/` – Original-Bilder, `backups/` – Backup-Archive.
- `scripts/` – Kommandozeilen-Werkzeuge (Migration, Admin anlegen,
  Dateiliste erzeugen); von außen gesperrt.

## Weiterentwicklung laut Konzept (vorbereitet, nicht in V1)

- Mehrere Bilder je Kunstwerk (Tabelle `bilder` ist bereits 1:n ausgelegt).
- Admin-Regler für Akzentfarbe/Logo sowie mehrere Themes.
