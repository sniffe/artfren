# Kunstverwaltung

Web-Anwendung (PHP 8.1+, SQLite) zur Verwaltung von Kunstwerken mit frei
definierbaren Gruppen, zwei Nutzerrollen, Export- und Backup-Funktionen.
Umsetzung gemäß dem übergebenen technischen Konzept-Dokument.

Der angezeigte Name der Anwendung ist frei wählbar: einfach `APP_NAME` in
`src/config.php` anpassen (wirkt sich auf Seitentitel, Kopfzeile und
Login-Seite aus).

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
5. Sobald ein SSL-Zertifikat aktiv ist: in `src/config.php`
   `HTTPS_ERZWINGEN` auf `true` setzen.

### Updates einspielen

Neue Programmdateien einfach hochladen (die Ordner `data/`, `bilder/` und
`backups/` dabei nicht überschreiben). Nötige Datenbank-Änderungen laufen
beim nächsten Seitenaufruf automatisch (`migrations/NNN_*.sql`,
Versionsstand über `PRAGMA user_version`). Vorher ein Backup erstellen.

### Alternative mit Shell-Zugriff (lokale Entwicklung)

`composer install`, dann `php scripts/migrate.php` und
`php scripts/seed_admin.php <benutzername> <passwort>`. Die Skripte sind
nur über die Kommandozeile ausführbar.

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

- `*.php` im Wurzelverzeichnis – Einstiegspunkte (Controller).
- `src/` – Klassen (PSR-4 `App\`): `Auth`, `Database`, `Migration`,
  Repositories, `TabellenImport`, `Export`, `Bilder`, `Backup`, `Protokoll`,
  `Sicherheitscheck`, `OrtBereinigung`.
- `templates/` – reine Ausgabe-Templates, keine Geschäftslogik/SQL.
- `assets/tokens.css` – alle Design-Tokens; `assets/style.css` verwendet nur
  diese Variablen. `assets/fonts/` – Schriften (SIL Open Font License).
- `migrations/` – nummerierte Schema-Migrationen.
- `data/` – Datenbank, Bild-Cache (`cache/`), Fehlerprotokoll (`logs/`).
- `bilder/` – Original-Bilder, `backups/` – Backup-Archive.

## Weiterentwicklung laut Konzept (vorbereitet, nicht in V1)

- Mehrere Bilder je Kunstwerk (Tabelle `bilder` ist bereits 1:n ausgelegt).
- Admin-Regler für Akzentfarbe/Logo sowie mehrere Themes.
