<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    /**
     * @return array{ok: bool, fehler?: string}
     */
    public static function login(string $benutzername, string $passwort): array
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM benutzer WHERE benutzername = :b');
        $stmt->execute(['b' => $benutzername]);
        $benutzer = $stmt->fetch();

        if ($benutzer === false) {
            // Keine Rückmeldung, ob der Benutzername existiert.
            return ['ok' => false, 'fehler' => 'Benutzername oder Passwort ist falsch.'];
        }

        $utc = new \DateTimeZone('UTC');

        if (!empty($benutzer['gesperrt_bis']) && new \DateTimeImmutable($benutzer['gesperrt_bis'], $utc) > new \DateTimeImmutable('now', $utc)) {
            $bis = Helpers::formatDatum($benutzer['gesperrt_bis'], 'H:i \U\h\r');
            return ['ok' => false, 'fehler' => "Konto ist gesperrt bis {$bis}. Bitte später erneut versuchen oder Admin um Entsperrung bitten."];
        }

        if (!password_verify($passwort, $benutzer['passwort_hash'])) {
            $fehlversuche = (int) $benutzer['fehlversuche'] + 1;
            $gesperrtBis = null;
            if ($fehlversuche >= LOGIN_MAX_FEHLVERSUCHE) {
                $gesperrtBis = (new \DateTimeImmutable('now', $utc))
                    ->modify('+' . LOGIN_SPERR_MINUTEN . ' minutes')
                    ->format('Y-m-d H:i:s');
            }

            $upd = $pdo->prepare('UPDATE benutzer SET fehlversuche = :f, gesperrt_bis = :g WHERE id = :id');
            $upd->execute(['f' => $fehlversuche, 'g' => $gesperrtBis, 'id' => $benutzer['id']]);

            if ($gesperrtBis !== null) {
                return ['ok' => false, 'fehler' => 'Zu viele Fehlversuche. Konto ist jetzt für ' . LOGIN_SPERR_MINUTEN . ' Minuten gesperrt.'];
            }

            return ['ok' => false, 'fehler' => 'Benutzername oder Passwort ist falsch.'];
        }

        $upd = $pdo->prepare('UPDATE benutzer SET fehlversuche = 0, gesperrt_bis = NULL, letzter_login = :jetzt WHERE id = :id');
        $upd->execute(['jetzt' => (new \DateTimeImmutable('now', $utc))->format('Y-m-d H:i:s'), 'id' => $benutzer['id']]);

        session_regenerate_id(true);
        $_SESSION['benutzer_id'] = (int) $benutzer['id'];

        return ['ok' => true];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function currentUser(): ?array
    {
        static $benutzer = null;
        static $geladen = false;

        if ($geladen) {
            return $benutzer;
        }
        $geladen = true;

        if (empty($_SESSION['benutzer_id'])) {
            return null;
        }

        $stmt = Database::get()->prepare('SELECT * FROM benutzer WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['benutzer_id']]);
        $row = $stmt->fetch();
        $benutzer = $row !== false ? $row : null;

        return $benutzer;
    }

    public static function requireLogin(): array
    {
        $benutzer = self::currentUser();
        if ($benutzer === null) {
            Helpers::redirect('/login.php');
        }

        return $benutzer;
    }

    public static function requireAdmin(): array
    {
        $benutzer = self::requireLogin();
        if ($benutzer['rolle'] !== 'admin') {
            http_response_code(403);
            exit('Zugriff verweigert: diese Funktion ist nur für Administratoren verfügbar.');
        }

        return $benutzer;
    }

    public static function isAdmin(array $benutzer): bool
    {
        return $benutzer['rolle'] === 'admin';
    }

    /**
     * Liefert die IDs der Gruppen, die ein eingeschränkter Benutzer sehen darf.
     * Für Admins ist der Rückgabewert irrelevant (die volle Liste wird ohnehin gezeigt).
     *
     * @return int[]
     */
    public static function sichtbareGruppenIds(array $benutzer): array
    {
        $stmt = Database::get()->prepare('SELECT gruppe_id FROM benutzer_gruppe WHERE benutzer_id = :id');
        $stmt->execute(['id' => $benutzer['id']]);

        return array_map('intval', array_column($stmt->fetchAll(), 'gruppe_id'));
    }

    public static function unlockUser(int $benutzerId): void
    {
        $stmt = Database::get()->prepare('UPDATE benutzer SET fehlversuche = 0, gesperrt_bis = NULL WHERE id = :id');
        $stmt->execute(['id' => $benutzerId]);
    }
}
