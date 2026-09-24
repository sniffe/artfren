<?php
declare(strict_types=1);

namespace App;

/**
 * Prüft per echter HTTP-Anfrage an den eigenen Server, ob geschützte Ordner
 * von außen erreichbar sind – etwa weil der Hoster .htaccess ignoriert.
 */
final class Sicherheitscheck
{
    public const OEFFENTLICH = 'oeffentlich';
    public const GESCHUETZT = 'geschuetzt';
    public const UNBEKANNT = 'unbekannt';

    /** @return array<string, string> Ordner => Ergebnis */
    public static function pruefeOrdner(): array
    {
        $basis = self::basisUrl();
        $ergebnis = [];
        foreach (['data', 'backups', 'bilder', 'src'] as $ordner) {
            $ergebnis[$ordner] = $basis === null ? self::UNBEKANNT : self::pruefeEinzeln($basis, $ordner);
        }
        return $ergebnis;
    }

    private static function basisUrl(): ?string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        // Nur plausible Hostnamen – verhindert, dass ein manipulierter Host-Header
        // den Server Anfragen an beliebige Ziele schicken lässt.
        if (!preg_match('/^[a-z0-9.-]+(:\d{1,5})?$/i', $host)) {
            return null;
        }
        $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        return ($https ? 'https://' : 'http://') . $host;
    }

    private static function pruefeEinzeln(string $basis, string $ordner): string
    {
        $inhalt = bin2hex(random_bytes(16));
        $datei = '.kv-pruefung-' . bin2hex(random_bytes(6)) . '.txt';
        $pfad = APP_ROOT . '/' . $ordner . '/' . $datei;
        if (@file_put_contents($pfad, $inhalt) === false) {
            return self::UNBEKANNT;
        }

        try {
            $antwort = self::abrufen($basis . '/' . $ordner . '/' . $datei);
        } finally {
            @unlink($pfad);
        }

        if ($antwort === null) {
            return self::UNBEKANNT;
        }
        return str_contains($antwort, $inhalt) ? self::OEFFENTLICH : self::GESCHUETZT;
    }

    /** Liefert den Antworttext bei Status 200, '' bei anderem Status, null wenn nicht prüfbar. */
    private static function abrufen(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                // Nur der eigene Server wird geprüft; ein selbstsigniertes Zertifikat
                // soll das Ergebnis nicht verhindern.
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $text = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($text === false || $status === 0) {
                return null;
            }
            return $status === 200 ? (string) $text : '';
        }

        if (!ini_get('allow_url_fopen')) {
            return null;
        }
        $kontext = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => 0, 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $text = @file_get_contents($url, false, $kontext);
        if ($text === false) {
            return null;
        }
        $kopf = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : ($http_response_header ?? []);
        $statuszeile = $kopf[0] ?? '';
        return str_contains($statuszeile, ' 200') ? $text : '';
    }
}
