<?php
declare(strict_types=1);

namespace App;

/** Erzeugt und cacht verkleinerte Bildversionen unter data/cache. */
final class Bilder
{
    public static function originalPfad(string $dateiname): string
    {
        return BILDER_PATH . '/' . basename($dateiname);
    }

    /**
     * Liefert den Pfad zur gewünschten Größe (erzeugt sie bei Bedarf) oder
     * null, wenn das Original fehlt oder nicht verarbeitet werden kann.
     */
    public static function pfad(string $dateiname, string $groesse): ?string
    {
        $original = self::originalPfad($dateiname);
        if (!is_file($original)) {
            return null;
        }
        if ($groesse === 'o' || !isset(BILD_GROESSEN[$groesse])) {
            return $original;
        }

        $ziel = CACHE_PATH . '/' . $groesse . '/' . sha1($dateiname) . '.jpg';
        if (is_file($ziel) && filemtime($ziel) >= filemtime($original)) {
            return $ziel;
        }

        return self::erzeuge($original, $ziel, BILD_GROESSEN[$groesse]) ? $ziel : null;
    }

    private static function erzeuge(string $quelle, string $ziel, int $maxBreite): bool
    {
        $info = @getimagesize($quelle);
        if ($info === false) {
            return false;
        }
        [$breite, $hoehe] = $info;

        // Ein dekodiertes Bild braucht grob Breite × Höhe × 5 Byte. Große
        // Kamerafotos sprengen sonst das memory_limit des Hosters.
        $benoetigt = (int) ($breite * $hoehe * 5 * 1.7) + memory_get_usage();
        if (!self::speicherReicht($benoetigt)) {
            error_log("Bild zu groß für verfügbaren Speicher: {$quelle} ({$breite}x{$hoehe})");
            return false;
        }

        $bild = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($quelle),
            IMAGETYPE_PNG => @imagecreatefrompng($quelle),
            IMAGETYPE_GIF => @imagecreatefromgif($quelle),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($quelle) : false,
            default => false,
        };
        if ($bild === false) {
            return false;
        }

        $bild = self::dreheNachExif($bild, $quelle, $info[2]);
        $breite = imagesx($bild);
        $hoehe = imagesy($bild);

        $neueBreite = min($maxBreite, $breite);
        $neueHoehe = max(1, (int) round($hoehe * $neueBreite / $breite));

        $klein = imagecreatetruecolor($neueBreite, $neueHoehe);
        imagefill($klein, 0, 0, imagecolorallocate($klein, 255, 255, 255));
        imagecopyresampled($klein, $bild, 0, 0, 0, 0, $neueBreite, $neueHoehe, $breite, $hoehe);
        imagedestroy($bild);

        $ordner = dirname($ziel);
        if (!is_dir($ordner)) {
            mkdir($ordner, 0755, true);
        }
        // Erst in temporäre Datei schreiben, damit parallele Aufrufe nie ein halbes Bild sehen.
        $tmp = $ziel . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $ok = imagejpeg($klein, $tmp, 85);
        imagedestroy($klein);

        return $ok && rename($tmp, $ziel);
    }

    private static function speicherReicht(int $benoetigt): bool
    {
        $limit = self::alsBytes((string) ini_get('memory_limit'));
        if ($limit <= 0 || $limit >= $benoetigt) {
            return true;
        }
        // Viele Hoster erlauben das Anheben zur Laufzeit.
        @ini_set('memory_limit', (string) (int) ceil($benoetigt / 1048576 + 32) . 'M');
        $neu = self::alsBytes((string) ini_get('memory_limit'));
        return $neu <= 0 || $neu >= $benoetigt;
    }

    public static function alsBytes(string $wert): int
    {
        $wert = trim($wert);
        if ($wert === '' || $wert === '-1') {
            return -1;
        }
        $zahl = (int) $wert;
        return match (strtolower(substr($wert, -1))) {
            'g' => $zahl * 1024 ** 3,
            'm' => $zahl * 1024 ** 2,
            'k' => $zahl * 1024,
            default => $zahl,
        };
    }

    /** Handyfotos speichern die Drehung oft nur als EXIF-Angabe. */
    private static function dreheNachExif(\GdImage $bild, string $quelle, int $typ): \GdImage
    {
        if ($typ !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $bild;
        }
        $exif = @exif_read_data($quelle);
        $winkel = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($winkel === 0) {
            return $bild;
        }
        $gedreht = imagerotate($bild, $winkel, 0);
        if ($gedreht === false) {
            return $bild;
        }
        imagedestroy($bild);
        return $gedreht;
    }

    public static function mimeTyp(string $pfad): string
    {
        $info = @getimagesize($pfad);
        return $info['mime'] ?? 'application/octet-stream';
    }

    /**
     * Prüft einen Dateinamen für den Bilder-Ordner. Behält den Originalnamen
     * (die Import-Tabelle verweist exakt darauf), verbietet aber Pfade,
     * versteckte Dateien und andere Endungen als Bildformate.
     */
    public static function gueltigerDateiname(string $name): ?string
    {
        $name = trim(basename(str_replace('\\', '/', $name)));
        if ($name === '' || str_starts_with($name, '.') || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            return null;
        }
        if (mb_strlen($name) > 200) {
            return null;
        }
        // Mehrfach-Endungen wie "bild.php.jpg" können auf falsch konfigurierten
        // Apache-Servern als Skript ausgeführt werden.
        if (preg_match('/\.(php\d?|phtml|phar|pht|cgi|pl|py|sh|asp|jsp)(\.|$)/i', $name)) {
            return null;
        }
        $endung = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($endung, BILD_ENDUNGEN, true) ? $name : null;
    }
}
