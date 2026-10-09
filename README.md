*This README is available in two languages: [English](#kunstverwaltung) · [Deutsch](#kunstverwaltung-deutsch)*

*Diese README ist in zwei Sprachen verfügbar: [English](#kunstverwaltung) · [Deutsch](#kunstverwaltung-deutsch)*

---

# Kunstverwaltung

Web application (PHP 8.3+, SQLite) for managing artworks with freely definable
groups, two user roles, export and backup functions. Version 1.2.5.

The application name displayed in the UI is configurable: change it as an admin
under **System → Settings** (affects page title, header and login page). The value
is stored in the database and survives updates; `APP_NAME` in `src/config.php` is
only the default.

## Setup (shared hosting, no shell/SSH access required)

No Composer, no terminal, no cron job needed – the complete installation runs in
the browser. The `vendor/` directory with all dependencies is already part of this
repository.

1. Upload the entire directory (including `vendor/`) to your web space, e.g. via
   FTP or the host's file manager. The document root must point directly to this
   directory.
2. Open `https://your-domain.tld/install.php` in a browser and follow the two steps:
   - **Set up database** (creates the SQLite file with a random, non-guessable name
     and all tables),
   - **Create administrator**.
   If the installer reports missing write permissions, make `data/`, `bilder/` and
   `backups/` writable on your host (e.g. permissions 755).
3. The installer automatically checks whether the protected folders (`data/`,
   `backups/`, `bilder/`, `src/`) are actually blocked from outside access. If a
   warning appears, the web server is not processing `.htaccess` – ask your host to
   enable it **before importing real data**. The check can be repeated at any time
   under "System".
4. `install.php` locks itself after setup and can also be deleted.
5. Once an SSL certificate is active: open the site via `https://` and enable
   "Force HTTPS" under **System → Settings**.

### Applying updates

Works, users, images, backups and settings are preserved during an update: they
live in `data/`, `bilder/` and `backups/` or in the database, and a new version
only brings its `.htaccess` protection into those folders.

1. Create a backup under **Backup** and download it (for emergencies).
2. Download the new version as a ZIP (on GitHub: "Code" → "Download ZIP") and
   unpack it.
3. Enable **show hidden files** in your FTP client (FileZilla:
   Server → Force showing hidden files) so that `.htaccess` files are included.
4. Upload the **contents** of the unpacked folder (not the folder itself) to the
   application folder on the server and let it overwrite everything.
5. Wait until the upload is complete (FileZilla: "Files in transfer queue" and
   "Failed transfers" show 0). `vendor/` has over a thousand files and often takes
   10–30 minutes.
6. Open any page and log in. Required database changes run automatically
   (`migrations/NNN_*.sql`, version tracked via `PRAGMA user_version`).
7. Check under **System**:
   - "Program completeness" reports all program files as present,
   - if **outdated files** from previous versions are shown: "Delete now".
8. Optional: delete the `install.php` that came back (it is locked once an
   administrator exists).

**One-time step when updating from a version without "System → Settings":** The
application name was previously set in `src/config.php`; this file is overwritten
during updates. Enter the name once under **System → Settings** – from then on it
is preserved through all updates.

#### If something is wrong after the update

- **Page "The application has not been completely uploaded":** The page names the
  affected folders (checked by `upload_pruefung.php` before anything else loads).
  Wait for the upload or re-upload these folders and overwrite. If after the upload
  everything is one level too deep (e.g. `artfren-…/src`), move the contents up one
  level.
- **Error "Failed opening required …/src/bootstrap.php":** This comes from an older
  version without the upload check – the `src/` folder is missing. Upload the
  current version completely.
- **Red warning "Missing protection files (.htaccess)":** The FTP client skipped
  hidden files. Repeat steps 3 and 4, otherwise private folders may be accessible
  from outside.
- **"An error occurred" with error ID:** Details are in `data/logs/php-fehler.log`
  (download via FTP and search for the error ID).

For developers: before each new version, run `php scripts/dateiliste.php` to keep
`src/dateiliste.txt` (the basis for the completeness check) up to date, then
`php scripts/code_pruefung.php`. It must end with "OK". GitHub runs the same check
on every push (see "New in version 1.2.2").

### Updating to 1.1

1. **Create a backup** – in the admin area under **Backup** → "Create backup",
   download the file and keep it locally.
2. **Upload files** – upload all version 1.1 files via FTP and overwrite everything.
   Enable **hidden files** in your FTP client so that `.htaccess` files in `data/`,
   `bilder/`, `backups/` and `src/` are included.
3. **Open any page** – database migrations run automatically. A snapshot of the old
   database is saved beforehand under `data/` (`pre-migration-*-to-8-*.sqlite`).
4. **Verify** – under **System**: application version `1.1.0`, schema version `8 / 8`,
   snapshot file present.

Works, images, backups and settings are not affected.

**Rollback:** re-upload the v1.0 files (the schema is backwards-compatible; v1.0
code runs on a v1.1 schema). To also roll back data: use the snapshot file as the
new database (rename to `kv_*.sqlite`, delete the current DB) or restore the backup
ZIP as described in `LIESMICH.txt` inside the archive.

The full checklist for staging and live deployment is in `RELEASE_CHECKLIST_V1_1.md`.

### Updating to 1.2

1. **Create a backup** – in the admin area under **Backup** → "Create backup",
   download the file and keep it locally.
2. **Note for restricted users:** After the update, Excel export, images ZIP and
   original image downloads are disabled for all restricted users until an admin
   re-enables them individually under **Administration → Users**.
3. **Upload files** – upload all version 1.2 files via FTP and overwrite everything.
   Enable **hidden files** in your FTP client.
4. **Open any page** – database migrations run automatically. A snapshot of the old
   database is saved beforehand (`pre-migration-*-to-9-*.sqlite`).
5. **Verify** – under **System**: application version `1.2.5`, schema version `9 / 9`,
   snapshot file present, completeness check clear.
6. **Restore export permissions** for restricted users under **Administration → Users**
   (two new checkboxes per user: "Allow Excel export", "Allow image export (ZIP and originals)").

**Rollback:** re-upload the v1.1.0 files. To also roll back data: use the snapshot
file as the new database or restore the backup ZIP.

The full checklist is in `RELEASE_CHECKLIST_V1_2.md`.

### Alternative with shell access (local development)

`composer install`, then `php scripts/migrate.php` and
`php scripts/seed_admin.php <username> <password>`. The scripts are only executable
from the command line.

## Import

The Excel file can be **uploaded directly** (first worksheet): the header row is
found automatically, legend and sum rows are skipped, colour-coded rows are mapped
to status (red, orange, green); other colours are ignored. CSV (UTF-8 or
Windows-1252, `;`/`,`/tab) also works.

- On re-import, works are matched by Location + Artist + Title; identical
  combinations (e.g. multiple "o.T.") are assigned in order of age. Only mapped
  columns are updated.
- **Clean up locations** suggests spelling variants ("Top 17"/"Tio 17") and
  remembers merged spellings for future imports.
- **Upload images** (individually or as a ZIP): the filename must match the
  "Dateiname" column. Assignments are also saved when the file is missing – it
  appears automatically once uploaded.

## Export

The full export and group export each produce **two files** that belong together:

- **Excel** with all fields; the "Dateiname" column names the image file.
- **Images ZIP** with exactly those files (flat, no subdirectories). Missing files
  are listed in `FEHLENDE_BILDER.txt`.

Both can be re-imported unchanged: ZIP under "Upload images" (or via FTP to
`bilder/`), Excel under "Import table" – in any order. Each group also has a PDF
(one work per page or as a list); a dialog lets you pick fields and layout, optionally
from a saved export profile. Groups, users and settings are only included in the backup.

## Editing works

Use "+ New work" in the work list to create works without a spreadsheet (blank
form, image can be uploaded directly).

Admins can edit any work from the work list ("edit") or the detail view ("Edit"),
including the image (upload, assign from the image folder or remove the assignment).
In the work list, clicking the image placeholder opens an upload/assign dialog
directly.

Works edited in the application are protected before the next table import: the
preview lists them separately and they are only overwritten with an explicit
checkbox. If Location, Artist or Title is changed, the import still recognises the
work by its old name (no duplicate).

## Security

- Passwords with bcrypt; account lockout after 4 failed attempts (15 min.) plus
  throttling per IP address (20 failed attempts across all accounts).
- Sessions: HttpOnly/SameSite cookie, expiry after 60 min. of inactivity, password
  change logs out all other devices.
- CSRF protection for all forms, Content-Security-Policy without inline scripts.
- Images are only served through `bild.php` with permission checks.
- Exports are protected against formula injection in Excel.
- Fonts bundled locally (no data transfer to Google).
- Audit log of all important actions under "System".

## Structure

- `*.php` in root directory – entry points (controllers).
- `src/` – classes (PSR-4 `App\`): `Auth`, `Database`, `Migration`, repositories,
  `TabellenImport`, `Export`, `Bilder`, `Backup`, `Protokoll`, `Sicherheitscheck`,
  `OrtBereinigung`.
- `templates/` – pure output templates, no business logic/SQL.
- `assets/tokens.css` – all design tokens; `assets/style.css` uses only these
  variables. `assets/fonts/` – fonts (SIL Open Font License).
- `migrations/` – numbered schema migrations.
- `data/` – database, image cache (`cache/`), error log (`logs/`).
- `bilder/` – original images, `backups/` – backup archives.

## New in version 1.2.5

Mobile design and editing, no database change (schema stays 9).

- List of works on smartphones: one tile per work with the image at full
  screen width, below it artist and title like a label, then the details.
  Empty details are left out. The checkbox sits on the image.
- Work detail page on smartphones: image at full screen width.
- Mobile menu opens below the collection name instead of next to it.
- "Move to trash" is now on the edit page instead of the detail page.

## New in version 1.2.4

Update fixes, no database change (schema stays 9).

- After an update the browser automatically loads the new stylesheets and
  scripts (version tag on every file). Previously it could keep using the old,
  cached design, which broke the layout (for example huge icons in the menu).
- System completeness check: no more false alarms after an update from the
  GitHub ZIP. The libraries in `vendor/` leave out their tests and developer
  tools from that download; the file list now matches it exactly.

## New in version 1.2.3

Visual refinements, no database change (schema stays 9).

- Header: clear space between the collection name and the first menu item.
- Selection bar below the list of works (create group, move to trash) is now a
  slim bar with small buttons instead of a tall, dominant block. "Next: create
  group" is outlined instead of filled.
- Work detail page: "Edit" and "Move to trash" are small, unobtrusive buttons.
- Cause of the oversized trash icon fixed: icons in buttons now have a fixed
  size, and any icon without a size rule falls back to text size.
- Footer with version number centred and with spacing.
- Public gallery: the overview page shows the images again (the field filter
  for public data had also removed the image reference).
- Public gallery on smartphones (portrait and landscape): one work below the
  other, each image at full width in its own proportions.
- Restricted users with only one group go straight to that group after login;
  the group overview, the "Groups" menu item and the back link only appear with
  two or more groups. The group overview no longer offers Excel and image
  downloads to restricted users (on the group page they remain available if
  enabled for the user).
- Restricted users no longer see the purchase value ("Ankaufswert"): neither
  in the sums at the top of a group nor on the detail page of a work. The price
  remains visible. Exports are still governed by the export profiles.
- Excel export removed for restricted users; they only receive PDFs. The
  "Allow Excel export" option in user management is gone.
- PDF for restricted users without a chosen profile: the default profile is
  only used if it is released for restricted users, otherwise the first released
  profile. Previously a default profile not released for them (or, without any
  profile, all PDF fields including the purchase value) could end up in their PDF.
- PDF dialog: the close button (X) works, and the dialog closes after "Create PDF".
- PDF list layout no longer cuts off columns on the right: from 7 columns it
  uses landscape, column widths follow the content, long words wrap and the font
  size adapts to the number of columns.
- Code check: also reports inline scripts (onclick etc.) that the page's
  security policy blocks.

## New in version 1.2.2

Safeguards so that a defect like the one in 1.2.0 cannot go live unnoticed again.
No new features and no database change (schema stays 9).

- **Code check** `php scripts/code_pruefung.php`: checks PHP syntax, typographic
  quotation marks in code, HTML attributes and JavaScript, German quotation marks
  closed with a straight `"`, calls to classes or methods that do not exist,
  translation keys used in the code but missing in `lang/`, an outdated
  `src/dateiliste.txt` and the version number in `src/config.php` and this README.
- **GitHub Action** `.github/workflows/code-pruefung.yml` runs the check on every
  push. A red cross next to a commit means: do not release.
- **Self-test under System**: `src/dateiliste.txt` now contains a checksum for each
  file. Besides missing files, **System** also lists files that are present but do
  not match this version (interrupted upload, file from an older version, edited on
  the server).
- Release checklist: the code check must be green before the tag is set.
- Corrected German quotation marks in about 30 interface texts.

## New in version 1.2.1

Bugfix release, no new features and no database change (schema stays 9). Version 1.2.0
did not start (HTTP error 500): typographic quotation marks had slipped into the PHP
code and HTML templates, `src/I18n.php` mixed two namespace styles, one template was
missing an `endif`, and saving or duplicating export profiles called a method that does
not exist. Updating from 1.2.0 or 1.1: upload all program files as usual.

## New in version 1.2

### Language selection

Each user can select their interface language (Deutsch or English) in **My Account**.
The choice is saved in the database and survives logout/login and backup restores.
Admins can also set a language for each user and set the default language under
**System → Settings**.

To add a new language: copy `lang/en.php`, translate all values, then add one entry
to `I18n::SPRACHEN` in `src/I18n.php`.

### New columns in the work list

The work list now shows **Purchase year**, **Purchased from**, **Purchase value** and
**Price** (sortable). A sum row shows totals across all filtered works; the group page
shows both sums; the selection bar sums the ticked works.

### PDF export profiles

Before a PDF is generated, a dialog lets you select fields, layout (single sheet per
work or list with total row) and switches (show image, show title block, hide empty
fields, show total). The selection can be saved as a named **export profile** under
**Administration → Export → Profiles**. Profiles store fields, switches, layout and
optionally a fixed language for the PDF labels. Restricted users see only profiles
explicitly released for them.

### Export permissions per user

Admins always have full export access. For restricted users, access is now
**off by default** after the 1.2 update and must be enabled individually:

- **Allow Excel export**: the user sees the Excel button; export is limited to the
  selected group and the fields of a released export profile.
- **Allow image export (ZIP and originals)**: the Bilder-ZIP button appears and
  original-resolution images become accessible; without it, `bild.php?g=o` returns
  the 1600 px version.

## New in version 1.1

### Trash

Works can be moved to the trash instead of being deleted immediately. The trash is
accessible under **Administration → Trash**. From there, admins can restore works or
delete them permanently (image files are only removed if no other work references
them). The import recognises trash entries and does not create duplicates; permanently
deleted works leave a tombstone entry that also prevents the work from being
re-created on the next import.

### Multiple images per work

Each work can have up to 8 images. The order can be changed via drag-and-drop or
arrow buttons; the first image is the main image. On export, all images are included
in the ZIP and listed as `Dateiname`, `Dateiname 2` through `Dateiname 8` in the
Excel file.

### Publishing groups on the web

Admins can publish individual groups as a publicly accessible gallery:

- Each group receives a random, non-guessable link (`/w/<token>`).
- Individual works must be separately marked for web release (`web_freigabe = 1`).
- Configurable: page title, intro text, image mode (all images or main image only),
  which fields are visible, optional password, optional expiry date.
- Sensitive fields (location, purchase data, value, provenance) are off by default
  and marked with a warning.
- The link can be renewed at any time (invalidating the old one) or the group can be
  withdrawn.
- No login required; no cookie for open galleries. Password-protected galleries set
  a strictly necessary session cookie (HttpOnly, SameSite=Lax).
- Public pages carry `noindex` metadata.
- `robots.txt` excludes `/w/` from indexing.

Imprint and privacy policy can be entered under **Administration → Legal notices** as
text and/or an external link; the links appear in the footer of every public page.

### Appearance

Under **Administration → Appearance**:

- **Look** (separately for internal area and public galleries): "Gallery" (default),
  "Archive" (compact, cool tones) or "Contrast" (high contrast, larger text).
- **Accent colour**: any hex value; text colour on the accent is calculated
  automatically for good contrast (WCAG AA).
- **Font**: serif, grotesque or system font stack.
- **Icon weight**: regular, light or bold.
- **Logo**: PNG, JPG, WebP or SVG (max. 1 MB). SVG logos are served with an isolated
  Content-Security-Policy so that embedded scripts can never execute. Without a logo,
  the application name is shown as text.

### Data check

The page **Administration → Data check** shows statistics and links to filtered lists
for: works without an image, implausible years, empty/invalid technique, missing
value, duplicate key combinations, missing location, missing image files and
unassigned files in the images folder.

---

# Kunstverwaltung (Deutsch)

Web-Anwendung (PHP 8.3+, SQLite) zur Verwaltung von Kunstwerken mit frei
definierbaren Gruppen, zwei Nutzerrollen, Export- und Backup-Funktionen.
Version 1.2.5.

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
   Die Prüfung lässt sich jederzeit unter „System" wiederholen.
4. `install.php` sperrt sich nach der Einrichtung selbst und kann zusätzlich
   gelöscht werden.
5. Sobald ein SSL-Zertifikat aktiv ist: die Seite über `https://` aufrufen
   und unter **System → Einstellungen** „HTTPS erzwingen" einschalten.

### Updates einspielen

Werke, Benutzer, Bilder, Backups und Einstellungen bleiben bei einem Update
erhalten: Sie liegen in `data/`, `bilder/` und `backups/` bzw. in der
Datenbank, und eine neue Version bringt in diese Ordner nur ihre
`.htaccess`-Sperre mit.

1. Unter **Backup** ein Backup erstellen und herunterladen (für den Notfall).
2. Die neue Version als ZIP herunterladen (auf GitHub: „Code” → „Download
   ZIP”) und entpacken.
3. Im FTP-Programm **versteckte Dateien anzeigen** einschalten (FileZilla:
   Server → Anzeigen versteckter Dateien erzwingen), damit die
   `.htaccess`-Dateien mitkommen.
4. Den **Inhalt** des entpackten Ordners (nicht den Ordner selbst) in den
   Programmordner auf dem Server hochladen und alles überschreiben lassen.
5. Warten, bis das Hochladen fertig ist (FileZilla: „Zu übertragende
   Dateien” und „Fehlgeschlagene Übertragungen” stehen auf 0). `vendor/`
   hat über tausend Dateien und braucht oft 10–30 Minuten.
6. Irgendeine Seite aufrufen und anmelden. Nötige Datenbank-Änderungen
   laufen dabei automatisch (`migrations/NNN_*.sql`, Versionsstand über
   `PRAGMA user_version`).
7. Unter **System** kontrollieren:
   - „Vollständigkeit des Programms” meldet alle Programmdateien als
     vorhanden,
   - werden **veraltete Dateien** früherer Versionen angezeigt: „Jetzt
     löschen”.
8. Optional: das wieder mitgebrachte `install.php` löschen (es ist gesperrt,
   sobald ein Administrator existiert).

**Einmalig beim Update von einer Version ohne „System → Einstellungen”:** Der Name der
Anwendung wurde früher in `src/config.php` eingetragen; diese Datei wird beim
Update überschrieben. Den Namen daher einmal unter **System → Einstellungen**
eintragen – ab dann bleibt er bei allen Updates erhalten.

#### Wenn nach dem Update etwas nicht stimmt

- **Seite „Das Programm ist noch nicht vollständig hochgeladen”:** Die Seite
  nennt die betroffenen Ordner (geprüft von `upload_pruefung.php`, bevor
  irgendetwas anderes geladen wird). Upload abwarten bzw. diese Ordner erneut
  hochladen und überschreiben. Liegt nach dem Upload alles eine Ebene zu tief
  (z. B. `artfren-…/src`), den Inhalt eine Ebene nach oben verschieben.
- **Englische Meldung „Failed opening required …/src/bootstrap.php”:** Stammt
  von einer älteren Version ohne Upload-Prüfung – der Ordner `src/` fehlt.
  Die aktuelle Version komplett hochladen.
- **Rote Warnung „Es fehlen Schutzdateien (.htaccess)”:** Das FTP-Programm
  hat versteckte Dateien ausgelassen. Schritt 3 und 4 wiederholen, sonst
  können private Ordner von außen abrufbar sein.
- **„Es ist ein Fehler aufgetreten” mit Fehler-ID:** Die Details stehen in
  `data/logs/php-fehler.log` (per FTP herunterladen und nach der Fehler-ID
  suchen).

Für Entwickler: Vor jeder neuen Version `php scripts/dateiliste.php`
ausführen, damit `src/dateiliste.txt` (Grundlage der
Vollständigkeitsprüfung) aktuell ist, danach `php scripts/code_pruefung.php`.
Die Prüfung muss mit „OK“ enden. GitHub führt sie bei jedem Push ebenfalls aus
(siehe „Neu in Version 1.2.2“).

### Update auf 1.1

1. **Backup erstellen** – im Admin-Bereich unter **Backup** → „Backup erstellen",
   Datei herunterladen und lokal aufbewahren.
2. **Dateien hochladen** – alle Dateien der Version 1.1 per FTP hochladen und
   alles überschreiben. Im FTP-Programm **versteckte Dateien anzeigen**, damit
   die `.htaccess`-Dateien in `data/`, `bilder/`, `backups/` und `src/` mitkommen.
3. **Erste Seite aufrufen** – die Datenbank-Migrationen laufen automatisch.
   Vorher wird ein Snapshot der alten Datenbank unter `data/` gespeichert
   (`pre-migration-*-to-8-*.sqlite`).
4. **Prüfen** – unter **System**: Anwendungsversion `1.1.0`, Schema-Version `8 / 8`,
   Snapshot-Datei vorhanden.

Werke, Bilder, Backups und Einstellungen bleiben unberührt.

**Rollback:** v1.0-Dateien erneut hochladen (das Schema ist rückwärtskompatibel).
Wenn auch die Daten zurückgesetzt werden sollen: Snapshot-Datei als neue DB
einsetzen (umbenennen auf `kv_*.sqlite`, alte DB löschen) oder das Backup-ZIP
gemäß `LIESMICH.txt` im Archiv einspielen.

Die vollständige Checkliste für Staging- und Live-Deployment liegt in
`RELEASE_CHECKLIST_V1_1.md`.

### Update auf 1.2

1. **Backup erstellen** – im Admin-Bereich unter **Backup** → „Backup erstellen",
   Datei herunterladen und lokal aufbewahren.
2. **Hinweis für eingeschränkte Benutzer:** Nach dem Update sind Excel-Export,
   Bilder-ZIP und Originalbilder für alle eingeschränkten Benutzer gesperrt,
   bis du sie einzeln unter **Verwaltung → Benutzer** freischaltest.
3. **Dateien hochladen** – alle Dateien der Version 1.2 per FTP hochladen und
   alles überschreiben. Im FTP-Programm **versteckte Dateien anzeigen**.
4. **Erste Seite aufrufen** – Datenbank-Migrationen laufen automatisch. Vorher
   wird ein Snapshot gespeichert (`pre-migration-*-to-9-*.sqlite`).
5. **Prüfen** – unter **System**: Anwendungsversion `1.2.5`, Schema-Version `9 / 9`,
   Snapshot vorhanden, Vollständigkeitsprüfung ohne Befund.
6. **Export-Rechte** der eingeschränkten Benutzer unter **Verwaltung → Benutzer**
   setzen (zwei neue Häkchen je Benutzer: „Excel-Export erlauben",
   „Bilder-Export erlauben").

**Rollback:** v1.1.0-Dateien erneut hochladen. Wenn auch die Daten zurückgesetzt
werden sollen: Snapshot-Datei als neue DB einsetzen oder Backup-ZIP einspielen.

Die vollständige Checkliste liegt in `RELEASE_CHECKLIST_V1_2.md`.

### Alternative mit Shell-Zugriff (lokale Entwicklung)

`composer install`, dann `php scripts/migrate.php` und
`php scripts/seed_admin.php <benutzername> <passwort>`. Die Skripte sind
nur über die Kommandozeile ausführbar.

## Rollen

- **Administrator:** alles – Werke, Import, Export, Gruppen, Benutzer,
  Backup und System.
- **Eingeschränkt:** sieht nur die Gruppen, die ihm zugewiesen sind, samt
  deren Werken. Export-Rechte (Excel, Bilder-ZIP) werden einzeln vom Admin
  vergeben; das PDF steht immer zur Verfügung (eingeschränkt auf freigegebene
  Profile). Keine Bearbeitung.

## Import

Die Excel-Datei kann **direkt** hochgeladen werden (erstes Tabellenblatt):
Die Kopfzeile wird automatisch gefunden, Legenden- und Summenzeilen werden
übersprungen, farbig markierte Zeilen werden laut Legende zum Status (Rot,
Orange, Grün); andere Farben werden ignoriert. CSV (UTF-8 oder Windows-1252, `;`/`,`/Tab) funktioniert ebenfalls.

- Beim erneuten Import werden Werke über Ort + Maler + Titel wiedererkannt;
  gleiche Kombinationen (z. B. mehrere „o.T.") werden der Reihe nach
  zugeordnet. Nur zugeordnete Spalten werden aktualisiert.
- **Orte bereinigen** schlägt Tippvarianten vor („Top 17"/„Tio 17") und merkt
  sich zusammengeführte Schreibweisen für künftige Importe.
- **Bilder hochladen** (einzeln oder als ZIP): Der Dateiname muss der Spalte
  „Dateiname" entsprechen. Verknüpfungen werden auch gespeichert, wenn das
  Bild noch fehlt – es erscheint automatisch, sobald es hochgeladen ist.

## Export

Gesamtexport und Gruppenexport liefern jeweils **zwei Dateien**, die
zusammengehören:

- **Excel** mit allen Feldern; die Spalte „Dateiname" nennt die Bilddatei.
- **Bilder-ZIP** mit genau diesen Dateien (flach, ohne Unterordner). Fehlende
  Dateien stehen in `FEHLENDE_BILDER.txt`.

Beides lässt sich unverändert wieder einspielen: ZIP unter „Bilder hochladen"
(oder per FTP nach `bilder/`), Excel unter „Tabelle importieren" – in
beliebiger Reihenfolge. Pro Gruppe gibt es zusätzlich das PDF (ein Werk pro Seite oder als Liste);
ein Dialog erlaubt Feldauswahl und Layout, optional aus einem gespeicherten
Export-Profil. Gruppen, Benutzer und Einstellungen enthält nur das Backup.

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

Über „+ Neues Werk anlegen" in der Werkliste lassen sich Werke auch ohne
Tabelle erfassen (leeres Formular, Bild direkt mit hochladbar).

Admins können jedes Werk aus der Werkliste („bearbeiten") oder der
Detailansicht („Bearbeiten") ändern, inklusive Bild (hochladen, aus dem
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
- Protokoll aller wichtigen Aktionen unter „System".

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

## Neu in Version 1.2.5

Mobiles Design und Bearbeiten, keine Datenbankänderung (Schema bleibt 9).

- Werkliste am Smartphone: eine Kachel je Werk mit bildschirmbreitem Bild,
  darunter Künstler und Titel wie ein Etikett, dann die Angaben. Leere Angaben
  entfallen. Das Auswahlkästchen liegt auf dem Bild.
- Detailansicht eines Werks am Smartphone: Bild bildschirmbreit.
- Mobiles Menü öffnet sich unter dem Sammlungsnamen statt daneben.
- „In den Papierkorb“ steht jetzt auf der Bearbeiten-Seite statt auf der
  Ansehen-Seite.

## Neu in Version 1.2.4

Korrekturen rund ums Update, keine Datenbankänderung (Schema bleibt 9).

- Nach einem Update lädt der Browser die neuen Gestaltungs- und Skriptdateien
  automatisch (Versionskennung an jeder Datei). Bisher konnte er die alte,
  zwischengespeicherte Gestaltung weiterverwenden, und das Layout zerfiel
  (zum Beispiel riesige Symbole im Menü).
- Vollständigkeitsprüfung unter System: keine Fehlalarme mehr nach einem Update
  aus dem GitHub-ZIP. Die Bibliotheken in `vendor/` lassen in diesem Download
  ihre Tests und Entwicklerwerkzeuge weg; die Dateiliste entspricht ihm jetzt
  genau.

## Neu in Version 1.2.3

Optische Verbesserungen, keine Datenbankänderung (Schema bleibt 9).

- Kopfzeile: deutlicher Abstand zwischen Sammlungsname und erstem Menüpunkt.
- Auswahlleiste unter der Werkliste (Gruppe anlegen, In den Papierkorb) ist
  jetzt eine schmale Leiste mit kleinen Schaltflächen statt eines hohen,
  dominanten Blocks. „Weiter: Gruppe anlegen“ ist umrandet statt gefüllt.
- Detailansicht eines Werks: „Bearbeiten“ und „In den Papierkorb“ sind kleine,
  zurückhaltende Schaltflächen.
- Ursache des übergroßen Papierkorb-Symbols behoben: Symbole in Schaltflächen
  haben eine feste Größe, und jedes Symbol ohne eigene Größenregel fällt auf
  Textgröße zurück.
- Fußzeile mit Versionsnummer zentriert und mit Abstand.
- Öffentliche Galerie: Die Übersichtsseite zeigt wieder die Bilder (der
  Feldfilter für öffentliche Daten hatte auch den Bildverweis entfernt).
- Öffentliche Galerie am Smartphone (hoch und quer): ein Werk unter dem
  anderen, jedes Bild in voller Breite und im eigenen Seitenverhältnis.
- Eingeschränkte Benutzer mit nur einer Gruppe landen nach der Anmeldung direkt
  in dieser Gruppe; Gruppenübersicht, Menüpunkt „Gruppen“ und Zurück-Link gibt
  es erst ab zwei Gruppen. Die Gruppenübersicht bietet eingeschränkten Benutzern
  keinen Excel- und Bilder-Download mehr an (auf der Gruppenseite weiterhin,
  sofern für den Benutzer freigeschaltet).
- Eingeschränkte Benutzer sehen den Ankaufswert nicht mehr: weder in den Summen
  oben in einer Gruppe noch in der Detailansicht eines Werks. Der Preis bleibt
  sichtbar. Für Exporte gelten weiterhin die Export-Profile.
- Excel-Export für eingeschränkte Benutzer abgeschafft, sie erhalten nur noch
  PDFs. Die Option „Excel-Export erlauben“ in der Benutzerverwaltung entfällt.
- PDF für eingeschränkte Benutzer ohne gewähltes Profil: Das Standard-Profil
  wird nur verwendet, wenn es für eingeschränkte Benutzer freigegeben ist, sonst
  das erste freigegebene Profil. Bisher konnte ein nicht freigegebenes
  Standard-Profil (oder ohne Profil alle PDF-Felder samt Ankaufswert) in ihrem
  PDF landen.
- PDF-Dialog: Der Schließen-Knopf (X) funktioniert, und der Dialog schließt
  sich nach „PDF erstellen“.
- PDF-Layout „Liste“ schneidet rechts keine Spalten mehr ab: ab 7 Spalten im
  Querformat, Spaltenbreiten nach Inhalt, lange Wörter brechen um, Schriftgröße
  passt sich der Spaltenzahl an.
- Codeprüfung meldet zusätzlich Inline-Skripte (onclick usw.), die die
  Sicherheitsrichtlinie der Seite blockiert.

## Neu in Version 1.2.2

Absicherungen, damit ein Fehler wie in 1.2.0 nicht noch einmal unbemerkt live
geht. Keine neuen Funktionen und keine Datenbankänderung (Schema bleibt 9).

- **Codeprüfung** `php scripts/code_pruefung.php`: prüft PHP-Syntax,
  typografische Anführungszeichen in Code, HTML-Attributen und JavaScript,
  deutsche Anführungszeichen, die mit einem geraden `"` schließen, Aufrufe von
  Klassen oder Methoden, die es nicht gibt, Übersetzungsschlüssel, die im Code
  benutzt werden, aber in `lang/` fehlen, eine veraltete `src/dateiliste.txt`
  und die Versionsnummer in `src/config.php` und diesem README.
- **GitHub Action** `.github/workflows/code-pruefung.yml` führt die Prüfung bei
  jedem Push aus. Ein rotes Kreuz neben einem Commit heißt: nicht veröffentlichen.
- **Selbsttest unter System**: `src/dateiliste.txt` enthält jetzt je Datei eine
  Prüfsumme. Unter **System** erscheinen neben fehlenden auch Dateien, die zwar
  vorhanden sind, aber nicht zu dieser Version passen (abgebrochener Upload,
  Datei einer älteren Version, auf dem Server geändert).
- Release-Checkliste: Die Codeprüfung muss grün sein, bevor der Tag gesetzt wird.
- Deutsche Anführungszeichen in rund 30 Oberflächentexten korrigiert.

## Neu in Version 1.2.1

Fehlerbehebung, keine neuen Funktionen und keine Datenbankänderung (Schema bleibt 9).
Version 1.2.0 startete nicht (HTTP-Fehler 500): Typografische Anführungszeichen waren in
den PHP-Code und die HTML-Vorlagen geraten, `src/I18n.php` mischte zwei
Namespace-Schreibweisen, in einer Vorlage fehlte ein `endif`, und das Speichern oder
Duplizieren von Export-Profilen rief eine nicht vorhandene Methode auf. Update von 1.2.0
oder 1.1: alle Programmdateien wie gewohnt hochladen.

## Neu in Version 1.2

### Sprachauswahl

Jeder Benutzer kann seine Oberflächensprache (Deutsch oder English) unter
**Mein Konto** wählen. Die Einstellung wird in der Datenbank gespeichert und
bleibt nach Abmeldung/Anmeldung und Backup-Wiederherstellung erhalten. Admins
können die Sprache auch beim Benutzer-Bearbeiten setzen; die Standardsprache
wird unter **System → Einstellungen** festgelegt.

Neue Sprache hinzufügen: `lang/en.php` kopieren, alle Werte übersetzen, dann
einen Eintrag in `I18n::SPRACHEN` in `src/I18n.php` ergänzen.

### Neue Spalten in der Werkliste

Die Werkliste zeigt jetzt **Ankaufsjahr**, **Gekauft von**, **Ankaufswert** und
**Preis** (sortierbar). Eine Summenzeile zeigt die Gesamtsummen über alle
gefilterten Werke; die Gruppenseite zeigt beide Summen; die Auswahlleiste
summiert die angehakten Werke.

### PDF-Export-Profile

Vor der PDF-Erstellung öffnet ein Dialog, in dem Felder, Layout (ein Werk pro
Seite oder Liste mit Summenzeile) und Schalter (Bild anzeigen, Titelblock,
Leere Felder ausblenden, Summe anzeigen) gewählt werden können. Die Auswahl
lässt sich als benanntes **Export-Profil** unter **Verwaltung → Export → Profile**
speichern. Profile speichern Felder, Schalter, Layout und optional eine feste
Sprache für die PDF-Beschriftungen. Eingeschränkte Benutzer sehen nur Profile,
die explizit für sie freigegeben wurden.

### Export-Rechte je Benutzer

Admins haben stets vollen Exportzugriff. Für eingeschränkte Benutzer ist der
Zugriff nach dem Update auf 1.2 **standardmäßig gesperrt** und muss einzeln
aktiviert werden:

- **Excel-Export erlauben:** der Benutzer sieht den Excel-Button; der Export
  beschränkt sich auf die gewählte Gruppe und die Felder eines freigegebenen
  Export-Profils.
- **Bilder-Export erlauben (ZIP und Originaldateien):** der Bilder-ZIP-Button
  erscheint und Originalbilder werden abrufbar; ohne diese Berechtigung liefert
  `bild.php?g=o` die 1600-px-Version.

## Neu in Version 1.1

### Papierkorb

Werke lassen sich in den Papierkorb verschieben statt sofort zu löschen.
Der Papierkorb ist unter **Verwaltung → Papierkorb** zugänglich. Von dort können
Admins Werke wiederherstellen oder endgültig löschen (dabei werden Bilddateien
nur entfernt, wenn kein anderes Werk sie referenziert). Der Import erkennt
Papierkorb-Einträge und erstellt keine Duplikate; endgültig gelöschte Werke
hinterlassen einen Tombstone-Eintrag, der ebenfalls verhindert, dass das Werk
beim nächsten Import neu angelegt wird.

### Mehrere Bilder je Werk

Jedes Werk kann bis zu 8 Bilder haben. Die Reihenfolge ist per Drag-and-Drop
oder Pfeiltasten änderbar; das erste Bild gilt als Hauptbild. Beim Export
werden alle Bilder in die ZIP aufgenommen und als `Dateiname`, `Dateiname 2`
bis `Dateiname 8` in der Excel-Datei aufgeführt.

### Gruppen im Web veröffentlichen

Admins können einzelne Gruppen als öffentlich zugängliche Galerie freischalten:

- Jede Gruppe erhält einen zufälligen, nicht erratbaren Link (`/w/<token>`).
- Einzelne Werke müssen gesondert für die Web-Freigabe markiert werden
  (`web_freigabe = 1`).
- Wählbar: Seiten-Titel, Einleitungstext, Bildmodus (alle oder nur Hauptbild),
  welche Felder sichtbar sind, optionales Passwort, optionales Ablaufdatum.
- Sensible Felder (Ort, Ankaufsdaten, Wert, Herkunft) sind standardmäßig
  abgewählt und mit einem Hinweis versehen.
- Der Link lässt sich jederzeit erneuern (invalidiert den alten) oder die
  Gruppe wieder zurückgezogen werden.
- Kein Login nötig; kein Cookie für offene Galerien. Passwortgeschützte
  Galerien setzen ein strikt notwendiges Session-Cookie (HttpOnly, SameSite=Lax).
- Die öffentlichen Seiten sind mit `noindex`-Metadaten versehen.
- `robots.txt` schließt `/w/` von der Indexierung aus.

Impressum und Datenschutz lassen sich unter **Verwaltung → Rechtliche Angaben**
als Text und/oder externer Link hinterlegen; die Links erscheinen im Footer
jeder öffentlichen Seite.

### Erscheinungsbild

Unter **Verwaltung → Erscheinungsbild** lassen sich einstellen:

- **Look** (getrennt für internen Bereich und öffentliche Galerien):
  „Galerie" (Standard), „Archiv" (kompakt, kühl) oder „Kontrast" (hoher Kontrast,
  größere Schrift).
- **Akzentfarbe**: beliebiger Hex-Farbwert; Textfarbe wird automatisch für
  guten Kontrast (WCAG AA) berechnet.
- **Schrift**: Serif, Grotesque oder System-Schrift.
- **Icon-Stärke**: Normal, Leicht oder Fett.
- **Logo**: PNG, JPG, WebP oder SVG (max. 1 MB). SVG-Logos werden sicher mit
  isolierter Content-Security-Policy ausgeliefert, sodass enthaltene Skripte
  nie ausgeführt werden. Ohne Logo erscheint der App-Name als Text.

### Datenprüfung

Die Seite **Verwaltung → Datenprüfung** zeigt Statistiken und Links zu Filtern
für: Werke ohne Bild, implausible Jahreszahlen, leere/ungültige Technik,
fehlenden Wert, doppelte Schlüssel-Kombinationen, fehlenden Ort, fehlende
Bilddateien und nicht zugeordnete Dateien im Bilder-Ordner.
