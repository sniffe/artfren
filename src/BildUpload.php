<?php
declare(strict_types=1);

namespace App;

/** Nimmt Bilddateien oder ZIP-Archive entgegen und legt sie im Bilder-Ordner ab. */
final class BildUpload
{
    private const MAX_BILD = 50 * 1024 * 1024;
    private const MAX_ZIP_GESAMT = 1024 * 1024 * 1024;

    /** @var string[] */
    public array $gespeichert = [];
    /** @var string[] */
    public array $vorhanden = [];
    /** @var array<int, array{0: string, 1: string}> Name, Grund */
    public array $abgelehnt = [];

    public function __construct(private readonly bool $ueberschreiben)
    {
    }

    /** @param array $dateien $_FILES-Eintrag eines <input type="file" multiple> */
    public function verarbeite(array $dateien): void
    {
        @set_time_limit(300);
        foreach ((array) ($dateien['name'] ?? []) as $i => $name) {
            $name = (string) $name;
            $fehler = (int) ($dateien['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($fehler === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($fehler !== UPLOAD_ERR_OK) {
                $this->abgelehnt[] = [$name, $fehler === UPLOAD_ERR_INI_SIZE ? 'größer als vom Server erlaubt' : 'Upload fehlgeschlagen'];
                continue;
            }
            $tmp = (string) $dateien['tmp_name'][$i];
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'zip') {
                $this->entpacke($tmp, $name);
            } else {
                $this->uebernimm($tmp, $name, true);
            }
        }
    }

    private function entpacke(string $zipPfad, string $zipName): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPfad) !== true) {
            $this->abgelehnt[] = [$zipName, 'ZIP-Archiv konnte nicht geöffnet werden'];
            return;
        }

        $gesamt = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $eintrag = $zip->statIndex($i);
            $pfadImZip = (string) $eintrag['name'];
            if (str_ends_with($pfadImZip, '/') || str_contains($pfadImZip, '__MACOSX/') || str_starts_with(basename($pfadImZip), '.')) {
                continue;
            }
            // Schutz vor "ZIP-Bomben": entpackte Größe begrenzen, bevor etwas geschrieben wird.
            $gesamt += (int) $eintrag['size'];
            if ((int) $eintrag['size'] > self::MAX_BILD) {
                $this->abgelehnt[] = [$pfadImZip, 'größer als 50 MB'];
                continue;
            }
            if ($gesamt > self::MAX_ZIP_GESAMT) {
                $this->abgelehnt[] = [$zipName, 'Archiv entpackt größer als 1 GB – Rest übersprungen'];
                break;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'kv_bild_');
            $quelle = $zip->getStream($pfadImZip);
            $ziel = fopen($tmp, 'w');
            if ($quelle === false || $ziel === false) {
                $this->abgelehnt[] = [$pfadImZip, 'konnte nicht entpackt werden'];
                @unlink($tmp);
                continue;
            }
            stream_copy_to_stream($quelle, $ziel, self::MAX_BILD + 1);
            fclose($quelle);
            fclose($ziel);

            // Ordnerstruktur im ZIP wird ignoriert – nur der Dateiname zählt.
            $this->uebernimm($tmp, basename($pfadImZip), false);
            @unlink($tmp);
        }
        $zip->close();
    }

    private function uebernimm(string $tmp, string $originalname, bool $hochgeladen): void
    {
        $name = Bilder::gueltigerDateiname($originalname);
        if ($name === null) {
            $this->abgelehnt[] = [$originalname, 'kein erlaubter Bild-Dateiname (' . implode(', ', BILD_ENDUNGEN) . ')'];
            return;
        }
        if (filesize($tmp) > self::MAX_BILD) {
            $this->abgelehnt[] = [$name, 'größer als 50 MB'];
            return;
        }
        // Inhalt prüfen, nicht nur die Endung: nur echte Bilder werden angenommen.
        $info = @getimagesize($tmp);
        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            $this->abgelehnt[] = [$name, 'Inhalt ist kein JPEG/PNG/GIF/WebP-Bild'];
            return;
        }

        $ziel = BILDER_PATH . '/' . $name;
        if (is_file($ziel) && !$this->ueberschreiben) {
            $this->vorhanden[] = $name;
            return;
        }

        $ok = $hochgeladen ? move_uploaded_file($tmp, $ziel) : copy($tmp, $ziel);
        if ($ok) {
            @chmod($ziel, 0644);
            $this->gespeichert[] = $name;
        } else {
            $this->abgelehnt[] = [$name, 'konnte nicht gespeichert werden (Schreibrechte bilder/?)'];
        }
    }

    /**
     * Speichert ein einzelnes, direkt einem Werk zugeordnetes Bild. Ist der Name
     * schon vergeben (z. B. "IMG_0001.jpg" vom Handy), wird ein freier Name
     * gewählt – hier verweist keine Tabelle auf den exakten Namen.
     *
     * @return array{0: ?string, 1: ?string} gespeicherter Dateiname, Fehlermeldung
     */
    public static function speichereEinzeln(array $datei): array
    {
        $fehler = (int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($fehler !== UPLOAD_ERR_OK) {
            return [null, $fehler === UPLOAD_ERR_INI_SIZE || $fehler === UPLOAD_ERR_FORM_SIZE
                ? 'Das Bild ist größer als vom Server erlaubt (' . ini_get('upload_max_filesize') . ').'
                : 'Das Bild konnte nicht hochgeladen werden.'];
        }
        $name = Bilder::gueltigerDateiname((string) $datei['name']);
        if ($name === null) {
            return [null, 'Nur Bilder mit der Endung ' . implode(', ', BILD_ENDUNGEN) . ' sind erlaubt.'];
        }
        $info = @getimagesize((string) $datei['tmp_name']);
        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return [null, 'Die Datei ist kein JPEG-, PNG-, GIF- oder WebP-Bild.'];
        }
        if ((int) $datei['size'] > self::MAX_BILD) {
            return [null, 'Das Bild ist größer als 50 MB.'];
        }

        $basis = pathinfo($name, PATHINFO_FILENAME);
        $endung = pathinfo($name, PATHINFO_EXTENSION);
        for ($n = 2; is_file(BILDER_PATH . '/' . $name); $n++) {
            $name = "{$basis}_{$n}.{$endung}";
        }
        if (!move_uploaded_file((string) $datei['tmp_name'], BILDER_PATH . '/' . $name)) {
            return [null, 'Das Bild konnte nicht gespeichert werden (Schreibrechte bilder/?).'];
        }
        @chmod(BILDER_PATH . '/' . $name, 0644);
        return [$name, null];
    }

    /**
     * Wertet ein Formular mit Datei-Feld und/oder "vorhandenes Bild" aus und
     * setzt das Ergebnis als Hauptbild des Werks. Ein hochgeladenes Bild hat
     * Vorrang.
     *
     * @return array{0: ?string, 1: ?string} zugewiesener Dateiname (null = nichts gewählt), Fehlermeldung
     */
    public static function zuweisenAusFormular(WerkRepository $repo, int $werkId, ?array $datei, string $vorhanden): array
    {
        if ($datei !== null && (int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$name, $fehler] = self::speichereEinzeln($datei);
            if ($fehler !== null) {
                return [null, $fehler];
            }
            $repo->setzeHauptbild($werkId, $name);
            return [$name, null];
        }

        $vorhanden = trim($vorhanden);
        if ($vorhanden === '') {
            return [null, null];
        }
        $name = Bilder::gueltigerDateiname($vorhanden);
        if ($name !== $vorhanden || !is_file(BILDER_PATH . '/' . $name)) {
            return [null, "Die Bilddatei „{$vorhanden}“ gibt es im Bilder-Ordner nicht."];
        }
        $repo->setzeHauptbild($werkId, $name);
        return [$name, null];
    }

    /** @return string[] Bilddateien im Ordner, die noch keinem Werk zugeordnet sind. */
    public static function unzugeordnet(): array
    {
        $vergeben = array_flip(Database::get()->query('SELECT DISTINCT dateiname FROM bilder')->fetchAll(\PDO::FETCH_COLUMN));
        $frei = [];
        foreach (new \DirectoryIterator(BILDER_PATH) as $datei) {
            $name = $datei->getFilename();
            if ($datei->isFile() && !isset($vergeben[$name]) && Bilder::gueltigerDateiname($name) === $name) {
                $frei[] = $name;
            }
        }
        natcasesort($frei);
        return array_values($frei);
    }

    /**
     * Dateinamen, auf die Werke verweisen, die aber im Bilder-Ordner fehlen.
     *
     * @return array<int, array{dateiname: string, anzahl: int, beispiel: string}>
     */
    public static function fehlendeBilder(): array
    {
        $stmt = Database::get()->query(
            "SELECT b.dateiname, COUNT(*) AS anzahl, MIN(k.maler || ' – ' || k.titel) AS beispiel
             FROM bilder b JOIN kunstwerke k ON k.id = b.kunstwerk_id
             GROUP BY b.dateiname ORDER BY b.dateiname COLLATE NOCASE"
        );
        $fehlend = [];
        foreach ($stmt as $zeile) {
            if (!is_file(Bilder::originalPfad($zeile['dateiname']))) {
                $fehlend[] = $zeile;
            }
        }
        return $fehlend;
    }
}
