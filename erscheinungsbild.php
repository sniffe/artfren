<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Einstellungen;
use App\Helpers;
use App\Protokoll;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    // ── Grundeinstellungen speichern ─────────────────────────────────────
    if ($aktion === 'speichern') {
        $lookIntern = (string) ($_POST['look_intern'] ?? 'galerie');
        if (!in_array($lookIntern, ['galerie', 'archiv', 'kontrast'], true)) {
            $lookIntern = 'galerie';
        }
        $lookWeb = (string) ($_POST['look_web'] ?? 'galerie');
        if (!in_array($lookWeb, ['galerie', 'archiv', 'kontrast'], true)) {
            $lookWeb = 'galerie';
        }

        $akzentfarbe = strtoupper(trim((string) ($_POST['akzentfarbe'] ?? '')));
        if (!preg_match('/^#[0-9A-F]{6}$/', $akzentfarbe)) {
            $akzentfarbe = '';
        }

        $schrift = (string) ($_POST['schrift'] ?? 'serif');
        if (!in_array($schrift, ['serif', 'grotesk', 'system'], true)) {
            $schrift = 'serif';
        }

        $iconStaerke = (string) ($_POST['icon_staerke'] ?? 'regular');
        if (!in_array($iconStaerke, ['regular', 'light', 'bold'], true)) {
            $iconStaerke = 'regular';
        }

        Einstellungen::setze('look_intern', $lookIntern);
        Einstellungen::setze('look_web', $lookWeb);
        Einstellungen::setze('akzentfarbe', $akzentfarbe);
        Einstellungen::setze('schrift', $schrift);
        Einstellungen::setze('icon_staerke', $iconStaerke);
        Protokoll::schreibe('erscheinungsbild', "look_intern={$lookIntern}, look_web={$lookWeb}, schrift={$schrift}");
        Helpers::flashSet('erfolg', t('erscheint.gespeichert'));
        Helpers::redirect('/erscheinungsbild.php');
    }

    // ── Logo hochladen ───────────────────────────────────────────────────
    if ($aktion === 'logo_hochladen') {
        $datei  = $_FILES['logo'] ?? null;
        $fehler = null;

        if (!is_array($datei) || ($datei['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $fehler = t('erscheint.logo_kein_bild');
        } elseif ((int) ($datei['size'] ?? 0) > 1_048_576) {
            $fehler = t('erscheint.logo_zu_gross');
        } else {
            $mime   = (string) mime_content_type($datei['tmp_name']);
            $endung = match ($mime) {
                'image/png'     => 'png',
                'image/jpeg'    => 'jpg',
                'image/webp'    => 'webp',
                'image/svg+xml' => 'svg',
                default         => null,
            };
            if ($endung === null) {
                $fehler = t('erscheint.logo_format');
            } else {
                $logoVerzeichnis = DATA_PATH . '/logo';
                if (!is_dir($logoVerzeichnis)) {
                    @mkdir($logoVerzeichnis, 0755, true);
                }

                // Altes Logo entfernen
                $altesLogo = Einstellungen::logoPfad();
                if ($altesLogo !== null) {
                    $alterPfad = $logoVerzeichnis . '/' . $altesLogo;
                    if (is_file($alterPfad)) {
                        @unlink($alterPfad);
                    }
                }

                $zielDatei = 'logo.' . $endung;
                $zielPfad  = $logoVerzeichnis . '/' . $zielDatei;

                if (move_uploaded_file((string) $datei['tmp_name'], $zielPfad)) {
                    Einstellungen::setze('logo_datei', $zielDatei);
                    Protokoll::schreibe('erscheinungsbild', "Logo hochgeladen: {$zielDatei}");
                    Helpers::flashSet('erfolg', t('erscheint.logo_gespeichert'));
                } else {
                    $fehler = t('erscheint.logo_schreiben');
                }
            }
        }
        if ($fehler !== null) {
            Helpers::flashSet('fehler', $fehler);
        }
        Helpers::redirect('/erscheinungsbild.php');
    }

    // ── Logo löschen ─────────────────────────────────────────────────────
    if ($aktion === 'logo_loeschen') {
        $logo = Einstellungen::logoPfad();
        if ($logo !== null) {
            $pfad = DATA_PATH . '/logo/' . $logo;
            if (is_file($pfad)) {
                @unlink($pfad);
            }
        }
        Einstellungen::setze('logo_datei', '');
        Protokoll::schreibe('erscheinungsbild', 'Logo gelöscht');
        Helpers::flashSet('erfolg', t('erscheint.logo_entfernt'));
        Helpers::redirect('/erscheinungsbild.php');
    }

    Helpers::redirect('/erscheinungsbild.php');
}

render('erscheinungsbild', [
    'titel'         => t('erscheint.titel'),
    'aktuelleSeite' => 'erscheinungsbild',
    'lookIntern'    => Einstellungen::look('intern'),
    'lookWeb'       => Einstellungen::look('web'),
    'akzentfarbe'   => Einstellungen::akzentfarbe() ?? '',
    'schrift'       => Einstellungen::schrift(),
    'iconStaerke'   => Einstellungen::iconStaerke(),
    'hatLogo'       => Einstellungen::logoPfad() !== null,
]);
