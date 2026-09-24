<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\BildUpload;
use App\Database;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

Auth::requireAdmin();
$repo = WerkRepository::neu();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$werk = $repo->finde($id);
if ($werk === null) {
    Helpers::abbrechen(404, 'Dieses Werk wurde nicht gefunden.');
}
$zurueck = Helpers::ruecksprung($_GET['zurueck'] ?? $_POST['zurueck'] ?? null, '/werk.php?id=' . $id);
$fehler = [];
$eingabe = $werk;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        Helpers::flashSet('fehler', 'Das Bild ist größer als vom Server erlaubt (' . ini_get('post_max_size') . '). Die Änderungen wurden nicht gespeichert.');
        Helpers::redirect('/werk_bearbeiten.php?id=' . $id);
    }
    Helpers::checkCsrf();

    $text = static fn(string $feld): ?string => ($w = trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST[$feld] ?? '')))) === '' ? null : $w;
    foreach (['ort', 'maler', 'titel', 'format', 'technik', 'ankauf'] as $feld) {
        $eingabe[$feld] = $text($feld);
        if ($eingabe[$feld] !== null && mb_strlen($eingabe[$feld]) > 500) {
            $fehler[] = "„{$feld}“ ist zu lang (höchstens 500 Zeichen).";
        }
    }
    if ($eingabe['maler'] === null && $eingabe['titel'] === null) {
        $fehler[] = 'Bitte mindestens Maler oder Titel angeben.';
    }

    foreach (['entstehungsjahr' => 'Entstehungsjahr', 'ankaufjahr' => 'Ankaufjahr'] as $feld => $label) {
        $roh = $text($feld);
        $eingabe[$feld] = Helpers::parseJahr($roh);
        if ($roh !== null && ($eingabe[$feld] === null || $eingabe[$feld] < 1000 || $eingabe[$feld] > 2200)) {
            $fehler[] = "{$label}: bitte eine vierstellige Jahreszahl angeben.";
        }
    }
    foreach (['ankaufswert' => 'Ankaufswert', 'wert' => 'Wert'] as $feld => $label) {
        $roh = $text($feld);
        $eingabe[$feld] = Helpers::parseBetrag($roh);
        if ($roh !== null && $eingabe[$feld] === null) {
            $fehler[] = "{$label}: bitte einen Betrag angeben (z. B. 1.500 oder 1500,50).";
        }
    }
    $eingabe['werktyp'] = ($_POST['werktyp'] ?? '') === 'Objekt' ? 'Objekt' : 'Bild';
    $status = (string) ($_POST['status_farbe'] ?? '');
    $eingabe['status_farbe'] = isset(Helpers::STATUS_FARBEN[$status]) ? $status : null;

    if ($fehler === []) {
        $geaendert = array_values(array_filter(
            WerkRepository::BEARBEITBARE_FELDER,
            static fn(string $f) => (string) ($werk[$f] ?? '') !== (string) ($eingabe[$f] ?? ''),
        ));
        if ($geaendert !== []) {
            $repo->aktualisieren($werk, $eingabe);
        }

        $bildMeldung = null;
        if (!empty($_POST['bild_entfernen'])) {
            $repo->setzeHauptbild($id, null);
            $bildMeldung = 'Bildzuordnung entfernt (die Datei bleibt im Bilder-Ordner).';
        } else {
            [$bildName, $bildFehler] = BildUpload::zuweisenAusFormular($repo, $id, $_FILES['bild'] ?? null, (string) ($_POST['vorhandenes_bild'] ?? ''));
            if ($bildFehler !== null) {
                $fehler[] = $bildFehler;
            } elseif ($bildName !== null) {
                $bildMeldung = "Bild „{$bildName}“ zugewiesen.";
            }
        }

        if ($fehler === []) {
            $details = trim(($eingabe['maler'] ?? '') . ' – ' . ($eingabe['titel'] ?? ''), ' –');
            if ($geaendert !== []) {
                $details .= ' (' . implode(', ', $geaendert) . ')';
            }
            if ($bildMeldung !== null) {
                $details .= ' · ' . $bildMeldung;
            }
            Protokoll::schreibe('werk_bearbeitet', $details);
            Helpers::flashSet('erfolg', ($geaendert !== [] || $bildMeldung !== null) ? 'Änderungen wurden gespeichert.' : 'Es gab keine Änderungen.');
            Helpers::redirect($zurueck);
        }
    }
}

$bilder = Database::get()->prepare('SELECT * FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 1 ORDER BY sortierung, id LIMIT 1');
$bilder->execute(['id' => $id]);

render('werk_bearbeiten', [
    'titel' => 'Werk bearbeiten',
    'aktuelleSeite' => 'werke',
    'werk' => $werk,
    'eingabe' => $eingabe,
    'fehler' => $fehler,
    'zurueck' => $zurueck,
    'hauptbild' => $bilder->fetch() ?: null,
    'orte' => $repo->orte(),
    'malerListe' => $repo->maler(),
    'freieBilder' => BildUpload::unzugeordnet(),
]);
