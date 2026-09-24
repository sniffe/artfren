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
