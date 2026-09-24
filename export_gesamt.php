<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Export;
use App\Protokoll;
use App\WerkRepository;

Auth::requireAdmin();
$format = (string) ($_GET['format'] ?? '');
$repo = WerkRepository::neu();

if (!in_array($format, ['xlsx', 'bilder', 'csv'], true)) {
    render('export_gesamt', [
        'titel' => 'Gesamtexport',
        'aktuelleSeite' => 'export',
        'anzahlWerke' => $repo->zaehle([]),
        'bilder' => Export::bilderZuWerken($repo->alle()),
    ]);
    exit;
}

Protokoll::schreibe('export_gesamt', $format === 'bilder' ? 'Bilder-ZIP' : strtoupper($format));
session_write_close();
@set_time_limit(0);

if ($format === 'bilder') {
    Export::sendeBilderZip($repo->alle(), Export::dateiname('kunstwerke_bilder', 'zip'));
} else {
    Export::sendeTabelle($repo->alle(), $format, 'kunstwerke_gesamt');
}
