# Kunstverwaltung

Web-Anwendung (PHP 8.1+, SQLite) zur Verwaltung von Kunstwerken mit frei
definierbaren Gruppen, zwei Nutzerrollen, Export- und Backup-Funktionen.
Umsetzung gemäß dem übergebenen technischen Konzept-Dokument.

Der angezeigte Name der Anwendung ist frei wählbar: einfach `APP_NAME` in
`src/config.php` anpassen (wirkt sich auf Seitentitel, Kopfzeile und
Login-Seite aus).

## Einrichtung (Shared-Hosting, kein Root-Zugriff, kein Cronjob nötig)

1. Dieses Verzeichnis komplett auf den Webspace hochladen (Document Root
   zeigt direkt auf dieses Verzeichnis).
2. Abhängigkeiten installieren:
   ```
   composer install --no-dev
   ```
3. Datenbank anlegen:
   ```
   php scripts/migrate.php
   ```
4. Ersten Admin-Benutzer anlegen (löst das Henne-Ei-Problem):
   ```
   php scripts/seed_admin.php <benutzername> <passwort> "<echter Name>" "<email>"
   ```
5. Sicherstellen, dass der Webserver `.htaccess`-Dateien auswertet
   (Apache: `AllowOverride All` bzw. entsprechendes Preset beim Hoster).
   Die Ordner `data/`, `backups/`, `src/`, `templates/`, `migrations/`,
   `scripts/` und `vendor/` sind per `.htaccess` von außen gesperrt.
6. Schreibrechte für den Webserver-Benutzer auf `data/`, `bilder/`,
   `thumbs/`, `backups/` und `data/import_tmp/` sicherstellen.
7. Unter `/login.php` mit dem angelegten Admin-Konto anmelden.

## Struktur

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
