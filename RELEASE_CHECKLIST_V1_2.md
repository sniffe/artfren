# Release-Checkliste Version 1.2

Für Stefan. Zuerst auf Staging durchführen, dann nach erfolgreicher Prüfung auf Live. Zum Abhaken von oben nach unten.
Gilt für beide Ausgangslagen: Live läuft noch auf V1 (Schema 5) oder schon auf 1.1.0 (Schema 8).

---

## 1. Bevor du anfängst

- [ ] Claude Code hat alle Pakete gemeldet und die Abnahmepunkte bestanden.
- [ ] Auf GitHub liegt der fertige Stand im Branch `v1.2`.
- [ ] Die **Codeprüfung ist grün**: Auf GitHub steht beim neuesten Commit von `v1.2` ein grüner Haken (Details unter **Actions**). Bei rotem Kreuz nicht weitermachen, sondern Claude Code die Meldungen beheben lassen. Alternativ lokal: `php scripts/code_pruefung.php` endet mit „OK“.
- [ ] Ein ruhiger Zeitpunkt ist eingeplant (rund 1 Stunde für Staging, 20 Minuten für Live).
- [ ] Die eingeschränkten Benutzer sind informiert: Nach dem Update sind Excel-Export, Bilder-ZIP und Originalbilder für sie gesperrt, bis du sie freischaltest.
- [ ] Notiert: Welche Version läuft derzeit live (Fußzeile bzw. **System**)?

---

## 2. Staging einrichten

- [ ] Staging ist per `.htaccess`-Passwort geschützt und auf `noindex` gestellt (sie enthält echte Namen und Passwort-Hashes).
- [ ] In der Live-App unter **Backup** ein Backup **mit Bildern** erstellen und herunterladen. Datei gut aufheben.
- [ ] Backup in Staging einspielen: Datenbankdatei nach `data/kv_*.sqlite`, Bilder nach `bilder/`.
- [ ] Staging aufrufen und prüfen, dass es den alten Stand korrekt zeigt (Werkliste, ein Werk mit Bild, eine Gruppe).

---

## 3. Update auf Staging einspielen

- [ ] Branch `v1.2` als ZIP von GitHub herunterladen und entpacken.
- [ ] Im FTP-Programm **versteckte Dateien anzeigen** einschalten (wegen der `.htaccess`-Dateien).
- [ ] Alles hochladen und vorhandene Dateien überschreiben. `data/`, `bilder/`, `backups/` bringen nur ihre Schutzdatei mit, deine Daten bleiben.
- [ ] Eine Seite im Browser aufrufen. Die Migrationen laufen automatisch.
- [ ] Unter **System** prüfen:
  - Anwendungsversion `1.2.4`
  - Schema-Version `9 / 9`
  - In `data/` liegt eine Datei `pre-migration-*-to-9-*.sqlite` (Snapshot vor der Migration)
  - Vollständigkeitsprüfung ohne Befund: keine fehlenden und keine abweichenden Dateien, keine rote Warnung zu fehlenden Schutzdateien
- [ ] Falls "Das Programm ist noch nicht vollständig hochgeladen" erscheint: die genannten Ordner erneut hochladen.

---

## 4. Auf Staging testen

**Werkliste**
- [ ] Spalten Künstler, Ankaufsjahr, Gekauft von, Ankaufswert, Preis; Kopfzeile fett in Versalien, gleich groß wie die Zellen.
- [ ] Preise stimmen mit dem alten Stand überein (3 Werke mit dem Backup bzw. alter Excel vergleichen).
- [ ] Umblättern mit "erste" und "letzte" Seite; Sortieren nach jeder neuen Spalte.
- [ ] Summenzeile unter der Liste (mit Filter testen), Summe in der Auswahlleiste, Summen auf einer Gruppenseite.
- [ ] Detailseite und Bearbeiten-Modus zeigen die neuen Bezeichnungen. Ein Werk ändern, speichern, Änderung zurücknehmen.

**Sprache**
- [ ] Im Konto auf English umstellen und durch alle Bereiche klicken (auch Papierkorb, Datenprüfung, Erscheinungsbild). Nirgends deutscher Text außer Protokoll und deinen Daten.
- [ ] Einen Betrag auf Englisch eingeben (`1,200.50`), es muss 1.200,50 gespeichert werden. Danach zurück auf Deutsch.
- [ ] Unter **Rechtliche Angaben** einen englischen Impressumstext eintragen.
- [ ] Eine veröffentlichte Gruppe im privaten Fenster öffnen, einmal normal und einmal mit `?lang=en`.

**Exporte und Profile**
- [ ] Profil "Versicherung" anlegen (Layout Liste, Summe an), eine Gruppe als PDF exportieren, Summe mit der Gruppenseite vergleichen.
- [ ] Profil "Käufer" ohne Ankaufsdaten anlegen und für eingeschränkte Benutzer freigeben.
- [ ] Gesamtexport als Excel, diese Datei auf Staging wieder importieren: Ergebnis "keine Änderungen".
- [ ] Eine alte Excel-Datei (mit den Spalten Maler und Wert) importieren: ebenfalls "keine Änderungen".

**Rechte**
- [ ] Mit einem eingeschränkten Test-Benutzer anmelden: kein Excel-, kein Bilder-ZIP-Button, kein "Originalbild öffnen".
- [ ] Als Admin für diesen Benutzer "Excel-Export erlauben" einschalten. Als Test-Benutzer: Excel nur mit dem Profil "Käufer", nur Werke der gewählten Gruppe.
- [ ] "Bilder-Export erlauben" einschalten: ZIP enthält nur die Bilder der Gruppe, Originalbilder sind wieder abrufbar.

**Kurzer Gegencheck der 1.1-Funktionen**
- [ ] Papierkorb: ein Werk löschen und wiederherstellen.
- [ ] Mehrere Bilder: Reihenfolge ändern, Hauptbild wählen.
- [ ] Web-Veröffentlichung: Gruppe veröffentlichen, am Handy ansehen, zurückziehen.

**Wenn etwas nicht passt:** Bildschirmfoto und Uhrzeit notieren und an Claude Code oder an mich geben. Erst weitermachen, wenn alles passt.

---

## 5. Live einspielen

- [ ] In der Live-App **nochmals** ein Backup mit Bildern erstellen und herunterladen.
- [ ] Dieselbe ZIP-Datei wie auf Staging verwenden, versteckte Dateien anzeigen, alles hochladen und überschreiben.
- [ ] Unter **System**: Version 1.2.4, Schema 9 / 9, Snapshot vorhanden, keine Warnungen.
- [ ] Kurztest: Werkliste, ein Werk, eine Gruppe, ein PDF.
- [ ] Standardsprache unter **System** prüfen.
- [ ] Rechte der eingeschränkten Benutzer setzen (Excel, Bilder) und Export-Profile für sie freigeben.
- [ ] Tag `v1.2.4` setzen, und zwar erst jetzt und nur bei grüner Codeprüfung: auf GitHub unter **Releases** → **Draft a new release**, Tag `v1.2.4` neu anlegen, Target Branch `v1.2`. (Claude Code kann aus seiner Sitzung heraus keine Tags setzen.)

---

## 6. Notfall: zurück auf den alten Stand

- [ ] Programmdateien der vorherigen Version wieder hochladen (ZIP von Tag `v1.1.0` bzw. `v1.0`).
- [ ] Datenbank zurückholen: die Snapshot-Datei `pre-migration-*-to-9-*.sqlite` als `data/kv_*.sqlite` einsetzen, oder das heute heruntergeladene Backup wiederherstellen.
- [ ] Bei "Es ist ein Fehler aufgetreten" mit Fehler-ID: `data/logs/php-fehler.log` per FTP holen und die Fehler-ID mitschicken.
