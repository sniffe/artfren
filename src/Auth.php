<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    // Hash eines zufälligen, verworfenen Passworts: wird bei unbekanntem
    // Benutzernamen geprüft, damit die Antwortzeit nicht verrät, ob es das Konto gibt.
    private const DUMMY_HASH = '$2y$12$zciPTLAyOJZnDaRBNw9dJuIBUXSypSfBRI/VqA8e8gm4VJoFxH29O';

    private static ?array $benutzer = null;
    private static bool $geladen = false;
    /** @var array<int, int[]> */
    private static array $gruppenCache = [];

    public static function clientIp(): string
    {
        // Bewusst nur REMOTE_ADDR: X-Forwarded-For ist frei fälschbar und würde
        // die IP-Drosselung aushebeln.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt');
    }

    private static function jetztUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private static function ipGedrosselt(): bool
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*) FROM login_versuche WHERE ip = :ip AND zeitpunkt > datetime('now', :fenster)"
        );
        $stmt->execute(['ip' => self::clientIp(), 'fenster' => '-' . LOGIN_SPERR_MINUTEN . ' minutes']);
        return (int) $stmt->fetchColumn() >= LOGIN_MAX_VERSUCHE_PRO_IP;
    }

    private static function merkeFehlversuch(): void
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO login_versuche (ip) VALUES (:ip)')->execute(['ip' => self::clientIp()]);
        $pdo->exec("DELETE FROM login_versuche WHERE zeitpunkt < datetime('now', '-1 day')");
    }

    /**
     * @return array{ok: bool, fehler?: string}
     */
    public static function login(string $benutzername, string $passwort): array
    {
        $allgemein = 'Benutzername oder Passwort ist falsch.';

        if (self::ipGedrosselt()) {
            // Nicht protokollieren: sonst könnte ein Angreifer das Protokoll fluten.
            return ['ok' => false, 'fehler' => 'Zu viele fehlgeschlagene Anmeldungen von dieser Adresse. Bitte in ' . LOGIN_SPERR_MINUTEN . ' Minuten erneut versuchen.'];
        }

        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM benutzer WHERE benutzername = :b');
        $stmt->execute(['b' => $benutzername]);
        $benutzer = $stmt->fetch();

        if ($benutzer === false) {
            password_verify($passwort, self::DUMMY_HASH);
            self::merkeFehlversuch();
            Protokoll::schreibe('login_fehlgeschlagen', 'Unbekannter Benutzername: ' . $benutzername, []);
            return ['ok' => false, 'fehler' => $allgemein];
        }

        if (!empty($benutzer['gesperrt_bis']) && $benutzer['gesperrt_bis'] > self::jetztUtc()) {
            $bis = Helpers::formatDatum($benutzer['gesperrt_bis'], 'H:i \U\h\r');
            return ['ok' => false, 'fehler' => "Konto ist gesperrt bis {$bis}. Bitte später erneut versuchen oder einen Admin um Entsperrung bitten."];
        }

        if (!password_verify($passwort, $benutzer['passwort_hash'])) {
            self::merkeFehlversuch();
            $fehlversuche = (int) $benutzer['fehlversuche'] + 1;
            $gesperrtBis = $fehlversuche >= LOGIN_MAX_FEHLVERSUCHE
                ? gmdate('Y-m-d H:i:s', time() + LOGIN_SPERR_MINUTEN * 60)
                : null;

            $pdo->prepare('UPDATE benutzer SET fehlversuche = :f, gesperrt_bis = :g WHERE id = :id')
                ->execute(['f' => $fehlversuche, 'g' => $gesperrtBis, 'id' => $benutzer['id']]);

            if ($gesperrtBis !== null) {
                Protokoll::schreibe('konto_gesperrt', "Nach {$fehlversuche} Fehlversuchen", $benutzer);
                return ['ok' => false, 'fehler' => 'Zu viele Fehlversuche. Das Konto ist jetzt für ' . LOGIN_SPERR_MINUTEN . ' Minuten gesperrt.'];
            }
            Protokoll::schreibe('login_fehlgeschlagen', 'Falsches Passwort', $benutzer);
            return ['ok' => false, 'fehler' => $allgemein];
        }

        if (password_needs_rehash($benutzer['passwort_hash'], PASSWORD_BCRYPT)) {
            $pdo->prepare('UPDATE benutzer SET passwort_hash = :h WHERE id = :id')
                ->execute(['h' => password_hash($passwort, PASSWORD_BCRYPT), 'id' => $benutzer['id']]);
        }

        $pdo->prepare('UPDATE benutzer SET fehlversuche = 0, gesperrt_bis = NULL, letzter_login = :jetzt WHERE id = :id')
            ->execute(['jetzt' => self::jetztUtc(), 'id' => $benutzer['id']]);

        session_regenerate_id(true);
        $_SESSION['benutzer_id'] = (int) $benutzer['id'];
        $_SESSION['sitzung_version'] = (int) $benutzer['sitzung_version'];
        $_SESSION['letzte_aktivitaet'] = time();
        self::$geladen = false;

        Protokoll::schreibe('login', '', $benutzer);
        return ['ok' => true];
    }

    public static function logout(): void
    {
        $benutzer = self::currentUser();
        if ($benutzer !== null) {
            Protokoll::schreibe('logout', '', $benutzer);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$benutzer = null;
    }

    /** Beendet die Anmeldung, lässt aber die Sitzung für eine Hinweismeldung bestehen. */
    private static function beendeAnmeldung(string $hinweis): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        Helpers::flashSet('hinweis', $hinweis);
    }

    public static function currentUser(): ?array
    {
        if (self::$geladen) {
            return self::$benutzer;
        }
        self::$geladen = true;
        self::$benutzer = null;

        if (empty($_SESSION['benutzer_id'])) {
            return null;
        }

        if (time() - (int) ($_SESSION['letzte_aktivitaet'] ?? 0) > SITZUNG_LEERLAUF_MINUTEN * 60) {
            self::beendeAnmeldung('Die Sitzung wurde wegen Inaktivität beendet. Bitte erneut anmelden.');
            return null;
        }

        $stmt = Database::get()->prepare('SELECT * FROM benutzer WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['benutzer_id']]);
        $row = $stmt->fetch();

        // Gelöschter Benutzer oder Passwort inzwischen geändert → Sitzung ungültig.
        if ($row === false || (int) $row['sitzung_version'] !== (int) ($_SESSION['sitzung_version'] ?? 0)) {
            self::beendeAnmeldung('Bitte erneut anmelden.');
            return null;
        }

        $_SESSION['letzte_aktivitaet'] = time();
        self::$benutzer = $row;
        return $row;
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
        if (!self::isAdmin($benutzer)) {
            Helpers::abbrechen(403, 'Diese Funktion ist nur für Administratoren verfügbar.');
        }

        return $benutzer;
    }

    public static function isAdmin(array $benutzer): bool
    {
        return $benutzer['rolle'] === 'admin';
    }

    /** Eingeschränkte Benutzer haben keinen Zugriff auf die Werkliste. */
    public static function startseite(array $benutzer): string
    {
        return self::isAdmin($benutzer) ? '/werke.php' : '/gruppen.php';
    }

    /** @return int[] Gruppen, die ein eingeschränkter Benutzer sehen darf. */
    public static function sichtbareGruppenIds(array $benutzer): array
    {
        $id = (int) $benutzer['id'];
        if (!isset(self::$gruppenCache[$id])) {
            $stmt = Database::get()->prepare('SELECT gruppe_id FROM benutzer_gruppe WHERE benutzer_id = :id');
            $stmt->execute(['id' => $id]);
            self::$gruppenCache[$id] = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        }
        return self::$gruppenCache[$id];
    }

    public static function darfGruppeSehen(array $benutzer, int $gruppeId): bool
    {
        return self::isAdmin($benutzer) || in_array($gruppeId, self::sichtbareGruppenIds($benutzer), true);
    }

    public static function darfWerkSehen(array $benutzer, int $werkId): bool
    {
        if (self::isAdmin($benutzer)) {
            return true;
        }
        $sichtbar = self::sichtbareGruppenIds($benutzer);
        if ($sichtbar === []) {
            return false;
        }
        $stmt = Database::get()->prepare(
            'SELECT 1 FROM gruppe_kunstwerk WHERE kunstwerk_id = ? AND gruppe_id IN (' . Helpers::platzhalter($sichtbar) . ') LIMIT 1'
        );
        $stmt->execute(array_merge([$werkId], $sichtbar));
        return $stmt->fetchColumn() !== false;
    }

    /** Liefert den Bild-Datensatz, wenn der Benutzer ihn sehen darf, sonst null. */
    public static function erlaubtesBild(array $benutzer, int $bildId): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM bilder WHERE id = :id');
        $stmt->execute(['id' => $bildId]);
        $bild = $stmt->fetch();
        if ($bild === false || !self::darfWerkSehen($benutzer, (int) $bild['kunstwerk_id'])) {
            return null;
        }
        return $bild;
    }

    public static function verlangeGruppe(array $benutzer, int $gruppeId): void
    {
        if (!self::darfGruppeSehen($benutzer, $gruppeId)) {
            Helpers::abbrechen(403, 'Diese Gruppe ist dir nicht zugewiesen.');
        }
    }

    public static function unlockUser(int $benutzerId): void
    {
        Database::get()->prepare('UPDATE benutzer SET fehlversuche = 0, gesperrt_bis = NULL WHERE id = :id')
            ->execute(['id' => $benutzerId]);
    }

    /** Prüft ein neues Passwort; liefert eine Fehlermeldung oder null. */
    public static function passwortFehler(string $passwort, string $wiederholung): ?string
    {
        if (mb_strlen($passwort) < 8) {
            return 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }
        // bcrypt berücksichtigt nur die ersten 72 Bytes.
        if (strlen($passwort) > 72) {
            return 'Das Passwort darf höchstens 72 Bytes lang sein (Umlaute zählen doppelt).';
        }
        if ($passwort !== $wiederholung) {
            return 'Die beiden Passwörter stimmen nicht überein.';
        }
        return null;
    }

    /** Setzt ein neues Passwort und meldet alle anderen Sitzungen dieses Benutzers ab. */
    public static function setzePasswort(int $benutzerId, string $passwort): void
    {
        $pdo = Database::get();
        $pdo->prepare(
            'UPDATE benutzer SET passwort_hash = :h, sitzung_version = sitzung_version + 1, fehlversuche = 0, gesperrt_bis = NULL WHERE id = :id'
        )->execute(['h' => password_hash($passwort, PASSWORD_BCRYPT), 'id' => $benutzerId]);

        if ((int) ($_SESSION['benutzer_id'] ?? 0) === $benutzerId) {
            $stmt = $pdo->prepare('SELECT sitzung_version FROM benutzer WHERE id = :id');
            $stmt->execute(['id' => $benutzerId]);
            session_regenerate_id(true);
            $_SESSION['sitzung_version'] = (int) $stmt->fetchColumn();
        }
    }
}
