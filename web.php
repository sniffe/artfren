<?php
declare(strict_types=1);

// Öffentliche Galerie: /w/<token> (Grid), /w/<token>/werk/<id> (Detail)
// Rechtliche Seiten: /w/impressum, /w/datenschutz
// Passwort-Tor (POST): /w/<token>/passwort

require __DIR__ . '/src/bootstrap_public.php';

use App\Database;
use App\Einstellungen;
use App\Felder;
use App\GruppeRepository;
use App\WebGruppe;

$token = (string) ($_GET['t'] ?? '');
$rest  = trim((string) ($_GET['rest'] ?? ''), '/');

// ── Rechtliche Seiten (kein Token nötig) ──────────────────────────────────
if ($token === 'impressum' || $token === 'datenschutz') {
    $pdo = Database::get();
    $stmt = $pdo->prepare('SELECT wert FROM einstellungen WHERE schluessel = :s');

    $stmt->execute(['s' => $token . '_url']);
    $legalUrl = (string) ($stmt->fetchColumn() ?: '');
    if ($legalUrl !== '') {
        header('Location: ' . $legalUrl, true, 301);
        exit;
    }
    $stmt->execute(['s' => $token . '_text']);
    $legalText = (string) ($stmt->fetchColumn() ?: '');

    header('Cache-Control: no-store');
    renderPublic('web_legal', [
        'titel'      => $token === 'impressum' ? 'Impressum' : 'Datenschutz',
        'inhaltText' => $legalText,
    ]);
    exit;
}

// ── Token-Validierung ─────────────────────────────────────────────────────
if (!preg_match('/^[0-9a-f]{32}$/', $token)) {
    header('Cache-Control: no-store');
    http_response_code(404);
    renderPublic('web_fehler', []);
    exit;
}

$pdo  = Database::get();
$repo = GruppeRepository::neu();
$gruppe = $repo->findePerToken($token);

// Admin-Vorschau: auch inaktive Gruppen zeigen wenn Admin-Session aktiv ist.
$adminVorschau = false;
if (isset($_COOKIE['kv_sid'])) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_name('kv_sid');
    @session_start();
    $adminVorschau = \App\Auth::isAdmin(\App\Auth::currentUser());
    session_write_close();
}

if ($gruppe === null || (!WebGruppe::istZugaenglich($gruppe) && !$adminVorschau)) {
    header('Cache-Control: no-store');
    http_response_code(404);
    renderPublic('web_fehler', []);
    exit;
}

// ── CSP frame-ancestors ───────────────────────────────────────────────────
$einbettenVon = !empty($gruppe['web_einbetten_von'])
    ? preg_replace('/[^a-zA-Z0-9:._\-\/]/', '', (string) $gruppe['web_einbetten_von'])
    : null;
$frameAncestors = $einbettenVon !== null ? "'self' {$einbettenVon}" : "'none'";

// ── Passwort-Tor ──────────────────────────────────────────────────────────
if (WebGruppe::brauchPasswort($gruppe) && !WebGruppe::hatCookieZugang($gruppe) && !$adminVorschau) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rest === 'passwort') {
        $passwort = (string) ($_POST['passwort'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        WebGruppe::raeumeDrosselungAuf($pdo);
        if (WebGruppe::pruefePasswort($gruppe, $passwort, $ip, $pdo)) {
            WebGruppe::setzeCookie($gruppe, $token, $https);
            header('Location: /w/' . $token, true, 303);
            exit;
        }
        $passwortFehler = WebGruppe::istGedrosselt($gruppe, $ip, $pdo)
            ? 'Zu viele Fehlversuche. Bitte warten Sie einige Minuten.'
            : 'Das Passwort ist nicht korrekt.';
        header('Cache-Control: no-store');
        renderPublic('web_passwort', [
            'token'  => $token,
            'fehler' => $passwortFehler,
        ], $frameAncestors);
        exit;
    }
    header('Cache-Control: no-store');
    renderPublic('web_passwort', [
        'token'  => $token,
        'fehler' => null,
    ], $frameAncestors);
    exit;
}

// ── Aufruf-Zähler (nur Grid, kein Admin-Vorschau) ─────────────────────────
if (!$adminVorschau && $rest === '') {
    $repo->webAufrufErfassen((int) $gruppe['id']);
}

$erlaubteFelder = WebGruppe::erlaubteFelder($gruppe);
$gruppeId = (int) $gruppe['id'];

// ── Routing: Detail ───────────────────────────────────────────────────────
if (preg_match('#^werk/(\d+)$#', $rest, $m)) {
    $werkId = (int) $m[1];
    $werk   = $repo->werkOeffentlich($gruppeId, $werkId);
    if ($werk === null) {
        header('Cache-Control: no-store');
        http_response_code(404);
        renderPublic('web_fehler', [], $frameAncestors);
        exit;
    }
    $werkReduziert = WebGruppe::reduziereWerk($werk, $erlaubteFelder);

    // Bilder: nur in den Größen m und g; Original nie öffentlich
    $alleBilder = $repo->bilderOeffentlich($gruppeId, $werkId);
    if ((string) ($gruppe['web_bilder_modus'] ?? 'haupt') === 'haupt') {
        $alleBilder = array_values(
            array_filter($alleBilder, static fn($b) => (int) $b['ist_hauptbild'] === 1)
        );
    }

    // Prev/Next
    $alleWerkeIds = array_column($repo->werkeOeffentlich($gruppeId), 'id');
    $pos  = array_search($werkId, $alleWerkeIds, true);
    $prevId = ($pos !== false && $pos > 0) ? $alleWerkeIds[(int) $pos - 1] : null;
    $nextId = ($pos !== false && $pos < count($alleWerkeIds) - 1) ? $alleWerkeIds[(int) $pos + 1] : null;

    $sichtbareFelder = webSichtbareFelder($werkReduziert, $erlaubteFelder);

    header('Cache-Control: no-store');
    renderPublic('web_detail', [
        'gruppe'          => $gruppe,
        'werk'            => $werkReduziert,
        'bilder'          => $alleBilder,
        'sichtbareFelder' => $sichtbareFelder,
        'token'           => $token,
        'prevId'          => $prevId,
        'nextId'          => $nextId,
        'adminVorschau'   => $adminVorschau,
    ], $frameAncestors);
    exit;
}

// ── Routing: Grid ─────────────────────────────────────────────────────────
$werke = $repo->werkeOeffentlich($gruppeId);
$werkeReduziert = array_map(
    static fn($w) => WebGruppe::reduziereWerk($w, $erlaubteFelder),
    $werke
);

header('Cache-Control: no-store');
renderPublic('web_galerie', [
    'gruppe'        => $gruppe,
    'werke'         => $werkeReduziert,
    'token'         => $token,
    'adminVorschau' => $adminVorschau,
], $frameAncestors);

// ── Hilfsfunktionen ───────────────────────────────────────────────────────

/** Liefert Felddefinitionen der Felder, die im öffentlichen Detail angezeigt werden. */
function webSichtbareFelder(array $werk, array $erlaubteFelder): array
{
    $result = [];
    foreach (Felder::alle() as $feld) {
        if ($feld['oeffentlich'] === 'never') {
            continue;
        }
        if ($feld['oeffentlich'] === 'waehlbar' && !in_array($feld['key'], $erlaubteFelder, true)) {
            continue;
        }
        // maler und titel werden im Etikett-Header angezeigt, nicht in der Feldliste
        if (in_array($feld['key'], ['maler', 'titel'], true)) {
            continue;
        }
        $wert = $werk[$feld['key']] ?? null;
        if ($wert === null || $wert === '') {
            continue;
        }
        $result[] = $feld;
    }
    return $result;
}
