<?php
declare(strict_types=1);

namespace App;

final class Helpers
{
    // Markierungsfarben laut Legende der Excel-Tabelle. Andere Farben (etwa
    // alte gelbe/blaue Markierungen) ignoriert der Import.
    public const STATUS_FARBEN = [
        'rot' => 'Rot',
        'orange' => 'Orange',
        'gruen' => 'Grün',
    ];

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function checkCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            self::abbrechen(400, 'Die Anfrage ist abgelaufen oder ungültig. Bitte die Seite neu laden und erneut versuchen.');
        }
    }

    /** Zeigt eine Fehlerseite im normalen Layout und beendet die Anfrage. */
    public static function abbrechen(int $code, string $text): never
    {
        http_response_code($code);
        render('fehler', ['titel' => $code === 403 ? 'Kein Zugriff' : 'Fehler', 'text' => $text]);
        exit;
    }

    public static function formatGeld(?float $wert): string
    {
        if ($wert === null) {
            return '';
        }

        return number_format($wert, 2, ',', '.') . ' €';
    }

    /**
     * Formatiert einen in der DB gespeicherten Zeitstempel (immer UTC) für die
     * Anzeige in der App-Zeitzone.
     */
    public static function formatDatum(?string $isoDatum, string $format = 'd.m.Y H:i'): string
    {
        if (!$isoDatum) {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($isoDatum, new \DateTimeZone('UTC'));
            $dt = $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            return $dt->format($format);
        } catch (\Exception) {
            return $isoDatum;
        }
    }

    public static function formatGroesse(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2, ',', '.') . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
        }
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }

    /** Wandelt PHP-INI-Größenangaben wie "32M", "1G" in Bytes um. */
    public static function iniGroesseZuBytes(string $wert): int
    {
        $wert = trim($wert);
        $zahl = (int) $wert;
        $einheit = strtolower($wert[strlen($wert) - 1] ?? '');
        return match ($einheit) {
            'g' => $zahl * 1024 * 1024 * 1024,
            'm' => $zahl * 1024 * 1024,
            'k' => $zahl * 1024,
            default => $zahl,
        };
    }

    /** Berechnet die Gesamtgröße aller Dateien in einem Verzeichnis (nicht rekursiv). */
    public static function ordnerGroesse(string $pfad): int
    {
        $groesse = 0;
        foreach (new \DirectoryIterator($pfad) as $datei) {
            if ($datei->isFile()) {
                $groesse += $datei->getSize();
            }
        }
        return $groesse;
    }

    public static function flashSet(string $typ, string $nachricht): void
    {
        $_SESSION['flash'][] = ['typ' => $typ, 'nachricht' => $nachricht];
    }

    /** @return array<int, array{typ: string, nachricht: string}> */
    public static function flashGetAll(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Rücksprung-Adresse aus einem Formularfeld – nur interne Seiten, damit
     * niemand über einen präparierten Link auf fremde Seiten umleiten kann.
     */
    public static function ruecksprung(mixed $url, string $standard): string
    {
        if (is_string($url) && preg_match('~^/[a-z_]+\.php(\?[^\s#]*)?(#[\w-]+)?$~i', $url)) {
            return $url;
        }
        return $standard;
    }

    /** "?,?,?"-Platzhalter für IN(...)-Abfragen. */
    public static function platzhalter(array $liste): string
    {
        return implode(',', array_fill(0, count($liste), '?'));
    }

    /**
     * Bereinigt eine vom Browser gelieferte ID-Liste.
     *
     * @return int[]
     */
    public static function idListe(mixed $werte): array
    {
        if (!is_array($werte)) {
            return [];
        }
        $ids = array_map('intval', array_filter($werte, 'is_scalar'));
        return array_values(array_unique(array_filter($ids, static fn(int $id) => $id > 0)));
    }

    /**
     * URL für ein Bild über den geschützten Auslieferer bild.php.
     * $groesse: t (Thumbnail), m (Karte/PDF), g (Detail), o (Original).
     * Der Parameter v ändert sich mit der Datei, damit der Browser-Cache
     * nach einem neuen Upload nicht das alte Bild zeigt.
     */
    public static function bildUrl(?int $bildId, ?string $dateiname, string $groesse = 'm'): ?string
    {
        if (!$bildId || !$dateiname) {
            return null;
        }
        $mtime = @filemtime(BILDER_PATH . '/' . $dateiname);
        if ($mtime === false) {
            return null;
        }
        return '/bild.php?id=' . $bildId . '&g=' . $groesse . '&v=' . $mtime;
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_FARBEN[$status ?? ''] ?? '';
    }

    /**
     * Gibt ein SVG-Icon aus dem Sprite zurück.
     * Das Sprite muss im HTML-Body inline eingebettet sein (readfile im Layout).
     */
    public static function icon(string $name, string $klasse = ''): string
    {
        $attr = $klasse !== '' ? ' class="' . self::e($klasse) . '"' : '';
        return '<svg' . $attr . ' aria-hidden="true" focusable="false"><use href="#icon-' . self::e($name) . '"></use></svg>';
    }

    /** Freitext aus einer Tabelle ("Grün", "green", "gruen" …) → interner Statuswert. */
    public static function normalisiereStatus(?string $wert): ?string
    {
        if ($wert === null || trim($wert) === '') {
            return null;
        }
        $w = str_replace(['ü', 'ö'], ['ue', 'oe'], mb_strtolower(trim($wert)));
        $zuordnung = [
            'rot' => 'rot', 'red' => 'rot',
            'orange' => 'orange',
            'gelb' => 'gelb', 'yellow' => 'gelb',
            'gruen' => 'gruen', 'green' => 'gruen',
            'blau' => 'blau', 'blue' => 'blau',
            'violett' => 'violett', 'lila' => 'violett', 'purple' => 'violett',
        ];
        $status = $zuordnung[$w] ?? null;
        return isset(self::STATUS_FARBEN[$status ?? '']) ? $status : null;
    }

    /**
     * Ordnet eine Excel-Füllfarbe (ARGB, z. B. "FF00B050") über den Farbton
     * einem Status zu. Weiß/Grau und sehr blasse Töne gelten als "keine Markierung".
     */
    public static function statusAusFarbe(string $argb): ?string
    {
        $hex = substr(strtoupper($argb), -6);
        if (!preg_match('/^[0-9A-F]{6}$/', $hex)) {
            return null;
        }
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $saettigung = $max == 0 ? 0 : $delta / $max;
        if ($saettigung < 0.12 || $max < 0.15) {
            return null;
        }

        if ($max == $r) {
            $farbton = 60 * fmod((($g - $b) / $delta), 6);
        } elseif ($max == $g) {
            $farbton = 60 * ((($b - $r) / $delta) + 2);
        } else {
            $farbton = 60 * ((($r - $g) / $delta) + 4);
        }
        if ($farbton < 0) {
            $farbton += 360;
        }

        $farbe = match (true) {
            $farbton < 15 || $farbton >= 330 => 'rot',
            $farbton < 45 => 'orange',
            $farbton < 70 => 'gelb',
            $farbton < 170 => 'gruen',
            $farbton < 260 => 'blau',
            default => 'violett',
        };
        return isset(self::STATUS_FARBEN[$farbe]) ? $farbe : null;
    }

    /** "2005", "ca. 2005", "2005/06", 2005.0 → 2005 */
    public static function parseJahr(?string $wert): ?int
    {
        if ($wert === null || !preg_match('/\d{4}/', $wert, $m)) {
            return null;
        }
        return (int) $m[0];
    }

    /** "1.500,50 €", "1500.5", "1.500" (deutsch) → float */
    public static function parseBetrag(?string $wert): ?float
    {
        if ($wert === null) {
            return null;
        }
        $w = str_replace([' ', "\u{00A0}", '€', 'EUR'], '', $wert);
        if (str_contains($w, ',')) {
            $w = str_replace(['.', ','], ['', '.'], $w);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $w)) {
            $w = str_replace('.', '', $w);
        }
        return is_numeric($w) ? (float) $w : null;
    }

    /**
     * Kurzbezeichnung fuer Technik + Jahr: "Öl auf Leinwand, 1982".
     * Behandelt '/' und '-' als leere Technik, erzeugt kein fuehrendes Komma.
     */
    public static function werkMeta(?string $technik, mixed $jahr): string
    {
        $teile = [];
        $t = trim((string) ($technik ?? ''));
        if ($t !== '' && $t !== '/' && $t !== '-') {
            $teile[] = $t;
        }
        if ($jahr !== null && $jahr !== '' && (int) $jahr > 0) {
            $teile[] = (string) (int) $jahr;
        }
        return implode(', ', $teile);
    }

    /** Normalform eines Ortsnamens für Vergleiche und Aliase. */
    public static function ortSchluessel(?string $ort): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $ort)));
    }
}
