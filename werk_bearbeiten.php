<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\BildUpload;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

Auth::requireAdmin();
$repo = WerkRepository::neu();

// Ohne ID: leeres Formular zum Anlegen eines neuen Werks.
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$neu = $id === 0;
if ($neu) {
    $werk = array_fill_keys(WerkRepository::bearbeitbareFelder(), null);
    $werk = array_merge($werk, ['id' => 0, 'werktyp' => 'Bild', 'bearbeitet_am' => null, 'web_freigabe' => 0]);
} else {
    $werk = $repo->finde($id);
    if ($werk === null) {
        Helpers::abbrechen(404, 'Dieses Werk wurde nicht gefunden.');
    }
}
$zurueck = Helpers::ruecksprung($_GET['zurueck'] ?? $_POST['zurueck'] ?? null, $neu ? '/werke.php' : '/werk.php?id=' . $id);
$fehler = [];
$eingabe = $werk;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        Helpers::flashSet('fehler', 'Das Bild ist größer als vom Server erlaubt (' . ini_get('post_max_size') . '). Die Änderungen wurden nicht gespeichert.');
        Helpers::redirect('/werk_bearbeiten.php' . ($neu ? '' : '?id=' . $id));
    }
    Helpers::checkCsrf();

    // Bild-Reihenfolge: Up/Down-Buttons (Non-JS-Fallback) – sofort umleiten.
    if (!$neu) {
        $nachOben = array_keys((array) ($_POST['bild_verschieben_oben'] ?? []));
        $nachUnten = array_keys((array) ($_POST['bild_verschieben_unten'] ?? []));
        if ($nachOben !== []) {
            $repo->bildNachOben((int) $nachOben[0], $id);
            Helpers::redirect('/werk_bearbeiten.php?id=' . $id . '&zurueck=' . rawurlencode($zurueck));
        }
        if ($nachUnten !== []) {
            $repo->bildNachUnten((int) $nachUnten[0], $id);
            Helpers::redirect('/werk_bearbeiten.php?id=' . $id . '&zurueck=' . rawurlencode($zurueck));
        }
    }

    // Einzeilige Textfelder: Whitespace kollabieren
    $text = static fn(string $feld): ?string => ($w = trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST[$feld] ?? '')))) === '' ? null : $w;
    // Mehrzeilige Textfelder: Zeilenumbrüche erhalten, nur normalisieren
    $langtext = static fn(string $feld): ?string => ($w = trim((string) preg_replace('/\r\n?/', “\n”, (string) ($_POST[$feld] ?? '')))) === '' ? null : $w;

    foreach (['ort', 'maler', 'titel', 'format', 'technik', 'ankauf', 'herkunft', 'copyright'] as $feld) {
        $eingabe[$feld] = $text($feld);
        if ($eingabe[$feld] !== null && mb_strlen($eingabe[$feld]) > 500) {
            $fehler[] = “„{$feld}” ist zu lang (höchstens 500 Zeichen).”;
        }
    }
    foreach (['beschreibung_oeffentlich', 'beschreibung_intern'] as $feld) {
        $eingabe[$feld] = $langtext($feld);
        if ($eingabe[$feld] !== null && mb_strlen($eingabe[$feld]) > 5000) {
            $fehler[] = “„{$feld}” ist zu lang (höchstens 5000 Zeichen).”;
        }
    }
    if ($eingabe['maler'] === null && $eingabe['titel'] === null) {
        $fehler[] = 'Bitte mindestens Maler oder Titel angeben.';
    }

    foreach (['entstehungsjahr' => 'Entstehungsjahr', 'ankaufjahr' => 'Ankaufjahr'] as $feld => $label) {
        $roh = $text($feld);
        $eingabe[$feld] = Helpers::parseJahr($roh);
        if ($roh !== null && ($eingabe[$feld] === null || $eingabe[$feld] < 1000 || $eingabe[$feld] > 2200)) {
            $fehler[] = “{$label}: bitte eine vierstellige Jahreszahl angeben.”;
        }
    }
    foreach (['ankaufswert' => 'Ankaufswert', 'wert' => 'Wert'] as $feld => $label) {
        $roh = $text($feld);
        $eingabe[$feld] = Helpers::parseBetrag($roh);
        if ($roh !== null && $eingabe[$feld] === null) {
            $fehler[] = “{$label}: bitte einen Betrag angeben (z. B. 1.500 oder 1500,50).”;
        }
    }
    $eingabe['werktyp'] = ($_POST['werktyp'] ?? '') === 'Objekt' ? 'Objekt' : 'Bild';
    $status = (string) ($_POST['status_farbe'] ?? '');
    $eingabe['status_farbe'] = isset(Helpers::STATUS_FARBEN[$status]) ? $status : null;
    $eingabe['web_freigabe'] = isset($_POST['web_freigabe']) ? 1 : 0;

    if ($fehler === [] && $neu) {
        $neueId = $repo->anlegen($eingabe);
        $details = trim(($eingabe['maler'] ?? '') . ' – ' . ($eingabe['titel'] ?? ''), ' –');
        [$bildName, $bildFehler] = BildUpload::zuweisenAusFormular($repo, $neueId, $_FILES['bild'] ?? null, (string) ($_POST['vorhandenes_bild'] ?? ''));
        $extraAnzahl = 0;
        foreach ((array) (($_FILES['bilder_neu'] ?? [])['name'] ?? []) as $i => $name) {
            if ((int) ($_FILES['bilder_neu']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            [$fn, $fe] = BildUpload::speichereEinzeln([
                'name'     => $name,
                'tmp_name' => $_FILES['bilder_neu']['tmp_name'][$i],
                'error'    => $_FILES['bilder_neu']['error'][$i],
                'size'     => $_FILES['bilder_neu']['size'][$i],
            ]);
            if ($fn !== null) {
                $repo->bildHinzufuegen($neueId, $fn, null, $extraAnzahl + 1);
                $extraAnzahl++;
            }
            $bildFehler ??= $fe;
        }
        Protokoll::schreibe('werk_angelegt', $details . ($bildName !== null ? “ · Bild „{$bildName}”” : '') . ($extraAnzahl > 0 ? “ · {$extraAnzahl} weitere Bilder” : ''));

        if ($bildFehler !== null) {
            // Das Werk ist gespeichert – nur das Bild nicht. Direkt zum Nachreichen.
            Helpers::flashSet('fehler', “Das Werk wurde angelegt, ein Bild aber nicht gespeichert: {$bildFehler}”);
            Helpers::redirect('/werk_bearbeiten.php?id=' . $neueId);
        }
        Helpers::flashSet('erfolg', “Das Werk „{$details}” wurde angelegt.”);
        Helpers::redirect('/werk.php?id=' . $neueId);
    }

    if ($fehler === []) {
        $geaendert = array_values(array_filter(
            WerkRepository::bearbeitbareFelder(),
            static fn(string $f) => (string) ($werk[$f] ?? '') !== (string) ($eingabe[$f] ?? ''),
        ));
        if ($geaendert !== []) {
            $repo->aktualisieren($werk, $eingabe);
        }

        // Bilder entfernen
        foreach (Helpers::idListe($_POST['bild_entfernen'] ?? []) as $bildId) {
            $repo->bildEntfernen($bildId, $id);
        }

        // Beschriftungen aktualisieren
        foreach ((array) ($_POST['bild_beschriftung'] ?? []) as $bildId => $beschriftung) {
            $beschriftung = trim((string) preg_replace('/\s+/u', ' ', (string) $beschriftung));
            $repo->bildBeschriftung((int) $bildId, $id, $beschriftung === '' ? null : $beschriftung);
        }

        // Reihenfolge (JS-Pfad: bild_reihenfolge[] enthält sortierte IDs)
        if (!empty($_POST['bild_reihenfolge'])) {
            $repo->bildReihenfolge($id, array_map('intval', (array) $_POST['bild_reihenfolge']));
        }

        // Neue Bilder hochladen (Multi-Upload)
        $extraAnzahl = 0;
        foreach ((array) (($_FILES['bilder_neu'] ?? [])['name'] ?? []) as $i => $name) {
            if ((int) ($_FILES['bilder_neu']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            [$fn, $fe] = BildUpload::speichereEinzeln([
                'name'     => $name,
                'tmp_name' => $_FILES['bilder_neu']['tmp_name'][$i],
                'error'    => $_FILES['bilder_neu']['error'][$i],
                'size'     => $_FILES['bilder_neu']['size'][$i],
            ]);
            if ($fn !== null) {
                $repo->bildHinzufuegen($id, $fn, null, 100 + $extraAnzahl);
                $extraAnzahl++;
            } elseif ($fe !== null) {
                $fehler[] = $fe;
            }
        }

        // Vorhandenes Bild aus freien Bildern hinzufügen
        $vorhanden = trim((string) ($_POST['vorhandenes_bild'] ?? ''));
        if ($vorhanden !== '') {
            $vName = Bilder::gueltigerDateiname($vorhanden);
            if ($vName !== $vorhanden || !is_file(BILDER_PATH . '/' . $vName)) {
                $fehler[] = “Die Bilddatei „{$vorhanden}” gibt es im Bilder-Ordner nicht.”;
            } else {
                $repo->bildHinzufuegen($id, $vName, null, 100 + $extraAnzahl);
                $extraAnzahl++;
            }
        }

        if ($fehler === []) {
            $details = trim(($eingabe['maler'] ?? '') . ' – ' . ($eingabe['titel'] ?? ''), ' –');
            if ($geaendert !== []) {
                $details .= ' (' . implode(', ', $geaendert) . ')';
            }
            if ($extraAnzahl > 0) {
                $details .= “ · {$extraAnzahl} Bild” . ($extraAnzahl > 1 ? 'er' : '') . ' hinzugefügt';
            }
            Protokoll::schreibe('werk_bearbeitet', $details);
            $geaendertGesamt = $geaendert !== [] || $extraAnzahl > 0 || !empty($_POST['bild_entfernen']) || !empty($_POST['bild_beschriftung']);
            Helpers::flashSet('erfolg', $geaendertGesamt ? 'Änderungen wurden gespeichert.' : 'Es gab keine Änderungen.');
            Helpers::redirect($zurueck);
        }
    }
}

$bilder = $neu ? [] : $repo->bilder($id);

render('werk_bearbeiten', [
    'titel' => $neu ? 'Neues Werk' : 'Werk bearbeiten',
    'neu' => $neu,
    'aktuelleSeite' => 'werke',
    'werk' => $werk,
    'eingabe' => $eingabe,
    'fehler' => $fehler,
    'zurueck' => $zurueck,
    'bilder' => $bilder,
    'orte' => $repo->orte(),
    'malerListe' => $repo->maler(),
    'freieBilder' => BildUpload::unzugeordnet(),
]);
