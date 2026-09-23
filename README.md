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
2. Sicherstellen, dass der Webserver `.htaccess`-Dateien auswertet
   (bei Apache-Hostern in der Regel Standard). Die Ordner `data/`,
   `backups/`, `src/`, `templates/`, `migrations/`, `scripts/` und
   `vendor/` sind darüber von außen gesperrt.
3. Im Browser `https://deine-domain.tld/install.php` aufrufen und den
   zwei Schritten folgen:
   - **Datenbank einrichten** (legt die SQLite-Datei und alle Tabellen an),
   - **Administrator anlegen** (Benutzername, Name, E-Mail, Passwort).
   Zeigt der Installer fehlende Ordner-Schreibrechte an, müssen `data/`,
   `bilder/`, `thumbs/`, `backups/` und `data/import_tmp/` beim Hoster auf
   beschreibbar gestellt werden (z. B. Rechte 755).
4. Nach erfolgreicher Einrichtung ist `install.php` automatisch gesperrt
   (verweigert jede weitere Aktion, sobald ein Administrator existiert) –
   kann aber zusätzlich einfach vom Server gelöscht werden.
5. Unter `/login.php` mit dem angelegten Admin-Konto anmelden.

### Alternative für lokale Entwicklung mit Shell-Zugriff

Wer lokal (z. B. mit Docker/WSL) entwickelt und Composer-Abhängigkeiten
selbst aktualisieren möchte, kann weiterhin die CLI-Skripte nutzen:
`composer install`, dann `php scripts/migrate.php` und
`php scripts/seed_admin.php <benutzername> <passwort>`.

## Struktur

- `install.php` – einmaliger Web-Installer (Datenbank + erster Admin),
  sperrt sich nach erfolgreicher Einrichtung selbst.
- `*.php` im Wurzelverzeichnis – öffentliche Einstiegspunkte (Controller).
- `src/` – PHP-Klassen (Auth, Database, Helpers, CsvImport), PSR-4 `App\`.
- `templates/` – reine Ausgabe-Templates, keine Geschäftslogik/SQL.
- `assets/tokens.css` – alle Design-Tokens (Farben, Abstände, Schriften);
  `assets/style.css` referenziert ausschließlich diese Variablen.
- `migrations/schema.sql` – vollständiges Datenbankschema (7 Tabellen).
- `bilder/` – hochgeladene Kunstwerk-Bilder (Dateiname = CSV-Spalte
  „Dateiname“), `thumbs/` – automatisch generierter Thumbnail-Cache.

## Admin-Workflow

Import (CSV aus Excel-Blatt „Galerie“) → Werke durchsuchen/filtern →
Auswahl trifft (Häkchen, bleibt über Filter/Seiten hinweg erhalten) →
Gruppe anlegen → Gruppe exportieren (CSV/XLSX gesamt, ZIP oder PDF je
Gruppe) → regelmäßig manuell Backup erstellen.

## Weiterentwicklung laut Konzept (bewusst vorbereitet, nicht in V1)

- Mehrere Bilder je Kunstwerk (Tabelle `bilder` ist bereits 1:n ausgelegt).
- Admin-Regler für Akzentfarbe/Logo sowie mehrere Themes (siehe
  „Technische Trennung von Design und Funktion“ im Konzept).
