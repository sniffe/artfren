<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;
use App\WebGruppe;
use App\WerkRepository;

$benutzer = Auth::requireLogin();
$repo = GruppeRepository::neu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAdmin();
    Helpers::checkCsrf();

    $aktionGruppeId = (int) ($_POST['gruppe_id'] ?? 0);
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'mitglieder_speichern') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) {
            Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
        }
        $repo->setzeMitglieder((int) $gruppe['id'], Helpers::idListe($_POST['werk_ids'] ?? []));
        $anzahl = count(WerkRepository::neu()->mitgliedIds((int) $gruppe['id']));
        Protokoll::schreibe('gruppe_mitglieder', “„{$gruppe['name']}”: jetzt {$anzahl} Werke”);
        Helpers::flashSet('erfolg', “Mitglieder der Gruppe wurden aktualisiert ({$anzahl} Werke).”);
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id'] . '&mitglieder_gespeichert=1');
    }

    // ── Werk-Reihenfolge ──────────────────────────────────────────────────
    if ($aktion === 'werk_reihenfolge') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->setzeWerkReihenfolge((int) $gruppe['id'], array_map('intval', (array) ($_POST['werk_reihenfolge'] ?? [])));
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'werk_nach_oben') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->werkNachOben((int) $gruppe['id'], (int) ($_POST['werk_id'] ?? 0));
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'werk_nach_unten') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->werkNachUnten((int) $gruppe['id'], (int) ($_POST['werk_id'] ?? 0));
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    // ── Web-Veröffentlichung ──────────────────────────────────────────────
    if ($aktion === 'web_aktivieren') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->webAktivieren((int) $gruppe['id']);
        Protokoll::schreibe('web_veroeffentlicht', “„{$gruppe['name']}””);
        Helpers::flashSet('erfolg', 'Galerie wurde veröffentlicht.');
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'web_deaktivieren') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->webDeaktivieren((int) $gruppe['id']);
        Protokoll::schreibe('web_zurueckgezogen', “„{$gruppe['name']}””);
        Helpers::flashSet('erfolg', 'Galerie wurde zurückgezogen.');
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'web_link_erneuern') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $repo->webLinkErneuern((int) $gruppe['id']);
        Protokoll::schreibe('web_link_erneuert', “„{$gruppe['name']}””);
        Helpers::flashSet('erfolg', 'Der Link wurde erneuert. Der alte Link ist jetzt ungültig.');
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'web_einstellungen') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $einstellungen = [];

        $titel = trim((string) ($_POST['web_titel'] ?? ''));
        $einstellungen['web_titel'] = $titel !== '' ? $titel : null;

        $einleitung = trim((string) preg_replace('/\r\n?/', “\n”, (string) ($_POST['web_einleitung'] ?? '')));
        $einstellungen['web_einleitung'] = $einleitung !== '' ? $einleitung : null;

        $bilder_modus = ($_POST['web_bilder_modus'] ?? 'haupt') === 'alle' ? 'alle' : 'haupt';
        $einstellungen['web_bilder_modus'] = $bilder_modus;

        $einbettenVon = trim((string) ($_POST['web_einbetten_von'] ?? ''));
        $einstellungen['web_einbetten_von'] = $einbettenVon !== '' ? $einbettenVon : null;

        // Felder-Auswahl
        $gewaehlteFelder = array_values(array_filter(
            WebGruppe::feldPickerOptionen(),
            static fn($f) => isset($_POST['web_feld_' . $f['key']])
        ));
        $einstellungen['web_felder'] = json_encode(array_column($gewaehlteFelder, 'key'));

        // Passwort
        $neuesPasswort = (string) ($_POST['web_passwort_neu'] ?? '');
        $passwortEntfernen = isset($_POST['web_passwort_entfernen']);
        if ($passwortEntfernen) {
            $einstellungen['web_passwort_hash'] = null;
        } elseif ($neuesPasswort !== '') {
            $einstellungen['web_passwort_hash'] = password_hash($neuesPasswort, PASSWORD_DEFAULT);
        }

        // Ablauf
        $ablaufTyp = (string) ($_POST['web_ablauf_typ'] ?? 'kein');
        if ($ablaufTyp === 'datum' && !empty($_POST['web_ablauf_datum'])) {
            $ablaufDatum = (string) $_POST['web_ablauf_datum'];
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $ablaufDatum, new \DateTimeZone('UTC'));
            $einstellungen['web_ablauf'] = $dt ? $dt->format('Y-m-d 23:59:59') : null;
        } elseif (in_array($ablaufTyp, ['7', '14', '30'], true)) {
            $tage = (int) $ablaufTyp;
            $einstellungen['web_ablauf'] = (new \DateTimeImmutable(“+{$tage} days”, new \DateTimeZone('UTC')))->format('Y-m-d 23:59:59');
        } else {
            $einstellungen['web_ablauf'] = null;
        }

        $repo->webEinstellungenSpeichern((int) $gruppe['id'], $einstellungen);
        Protokoll::schreibe('web_felder_geaendert', “„{$gruppe['name']}””);
        Helpers::flashSet('erfolg', 'Web-Einstellungen gespeichert.');
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    if ($aktion === 'web_alle_freigeben') {
        $gruppe = $repo->finde($aktionGruppeId);
        if ($gruppe === null) { Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.'); }
        $anzahl = $repo->alleWerkeFreigeben((int) $gruppe['id']);
        Protokoll::schreibe('web_felder_geaendert', “„{$gruppe['name']}”: {$anzahl} Werke freigegeben”);
        Helpers::flashSet('erfolg', “{$anzahl} Werke wurden für Web freigegeben.”);
        Helpers::redirect('/gruppe.php?id=' . $gruppe['id']);
    }

    Helpers::redirect('/gruppen.php');
}

$id = (int) ($_GET['id'] ?? 0);
$gruppe = $repo->finde($id);
if ($gruppe === null || !Auth::darfGruppeSehen($benutzer, $id)) {
    Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
}

$istAdmin = Auth::isAdmin($benutzer);
$werke = WerkRepository::neu()->fuerGruppeExport($id);
$werkeSortiert   = $istAdmin ? $repo->fuerGruppeSortiert($id) : [];
$webStats        = $istAdmin ? $repo->webWerkStats($id) : ['gesamt' => 0, 'freigegeben' => 0];
$webFeldOptionen = $istAdmin ? \App\WebGruppe::feldPickerOptionen() : [];
$erlaubteFelder  = $istAdmin ? \App\WebGruppe::erlaubteFelder($gruppe) : [];
$https   = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$baseUrl = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
render('gruppe_ansicht', [
    'titel' => $gruppe['name'],
    'aktuelleSeite' => 'gruppen',
    'breit' => true,
    'gruppe' => $gruppe,
    'werke' => $werke,
    'bilderAnzahl' => count(\App\Export::bilderZuWerken($werke)['dateien']),
    'istAdmin' => $istAdmin,
    'werkeSortiert' => $werkeSortiert,
    'webStats' => $webStats,
    'webFeldOptionen' => $webFeldOptionen,
    'erlaubteFelder' => $erlaubteFelder,
    'baseUrl' => $baseUrl,
    'freigegebenFuer' => $istAdmin ? $repo->freigegebenFuer($id) : [],
    'auswahlVerwerfen' => array_filter([
        isset($_GET['neu']) ? 'auswahl_neu' : null,
        isset($_GET['mitglieder_gespeichert']) ? 'auswahl_gruppe_' . $id : null,
    ]),
]);
