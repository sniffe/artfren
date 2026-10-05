<?php
declare(strict_types=1);

namespace App;

/**
 * Zentrale Felddefinition aller Werke-Felder.
 *
 * Von hier leiten sich ab: bearbeitbareFelder() (WerkRepository),
 * zielfelder()/felder()/autoErkennung() (TabellenImport),
 * exportSpalten()/zahlenKeys() (Export),
 * Detail-Ansicht (werk_detail.php), PDF (pdf_gruppe.php).
 *
 * Eigenschaften je Feld:
 *   key             – DB-Spalte in kunstwerke
 *   label           – deutsches Label (Formular, Detail, Import-Dropdown)
 *   typ             – text | langtext | zahl | jahr | geld | auswahl | janein
 *   gruppe          – Stammdaten | Ankauf | Beschreibung | Herkunft | Status
 *   import_alias    – normalisierte Spaltennamen fuer Auto-Erkennung
 *   export_label    – Spaltenüberschrift im Excel-Export
 *   in_detail       – in der Admin-Detailansicht (dl) anzeigen
 *   in_pdf          – in der PDF-Tabelle anzeigen
 *   oeffentlich     – never | waehlbar | immer  (fuer Paket 5)
 *   oeffentlich_std – Standardwert im oeffentlichen Feldpicker
 *   sensibel        – Warnhinweis im oeffentlichen Feldpicker
 *   pflicht         – Pflichtfeld (Metadaten, Nutzung im Formular-Controller)
 */
final class Felder
{
    private static ?array $cache = null;

    /** @return array<int, array<string, mixed>> */
    public static function alle(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [
            // ----- Stammdaten ------------------------------------------------
            [
                'key'            => 'ort',
                'label'          => 'Ort',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['ort', 'standort'],
                'export_label'   => 'Ort',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'maler',
                'label'          => 'Maler',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['maler', 'kuenstler', 'kuenstlerin'],
                'export_label'   => 'Maler',
                'in_detail'      => false, // wird im Etikett-Header angezeigt
                'in_pdf'         => false, // wird im PDF-Etikett angezeigt
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'titel',
                'label'          => 'Titel',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['titel'],
                'export_label'   => 'Titel',
                'in_detail'      => false, // wird im Etikett-Header angezeigt
                'in_pdf'         => false, // wird im PDF-Etikett angezeigt
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'format',
                'label'          => 'Format',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['format'],
                'export_label'   => 'Format',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'technik',
                'label'          => 'Technik',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['technik'],
                'export_label'   => 'Technik',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'entstehungsjahr',
                'label'          => 'Entstehungsjahr',
                'typ'            => 'jahr',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['entstehungsjahr', 'enstehungsjahr', 'jahr'],
                'export_label'   => 'Entstehungsjahr',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'werktyp',
                'label'          => 'Bild/Objekt',
                'typ'            => 'auswahl',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['bild', 'werktyp', 'typ'],
                'export_label'   => 'Bild',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            // ----- Ankauf ----------------------------------------------------
            [
                'key'            => 'ankaufjahr',
                'label'          => 'Ankaufjahr',
                'typ'            => 'jahr',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankaufjahr', 'ankaufsjahr'],
                'export_label'   => 'Ankaufjahr',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'ankauf',
                'label'          => 'Ankauf (bei wem)',
                'typ'            => 'text',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankauf'],
                'export_label'   => 'Ankauf',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'ankaufswert',
                'label'          => 'Ankaufswert (€)',
                'typ'            => 'geld',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankaufswert', 'ankaufwert'],
                'export_label'   => 'Ankaufswert',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'wert',
                'label'          => 'Wert (€)',
                'typ'            => 'geld',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['wert'],
                'export_label'   => 'Wert',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            // ----- Beschreibung ----------------------------------------------
            [
                'key'            => 'beschreibung_oeffentlich',
                'label'          => 'Beschreibung (öffentlich)',
                'typ'            => 'langtext',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['beschreibungoeffentlich', 'beschreibung'],
                'export_label'   => 'Beschreibung (öffentlich)',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'beschreibung_intern',
                'label'          => 'Beschreibung (intern)',
                'typ'            => 'langtext',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['beschreibungintern'],
                'export_label'   => 'Beschreibung (intern)',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'never',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'copyright',
                'label'          => 'Bildrechte / Copyright-Vermerk',
                'typ'            => 'text',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['copyright', 'bildrechte'],
                'export_label'   => 'Bildrechte',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'immer',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            // ----- Herkunft --------------------------------------------------
            [
                'key'            => 'herkunft',
                'label'          => 'Herkunft',
                'typ'            => 'text',
                'gruppe'         => 'Herkunft',
                'import_alias'   => ['herkunft'],
                'export_label'   => 'Herkunft',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            // ----- Status ----------------------------------------------------
            [
                'key'            => 'status_farbe',
                'label'          => 'Status',
                'typ'            => 'auswahl',
                'gruppe'         => 'Status',
                'import_alias'   => ['status', 'statusfarbe', 'statuszellfarbe'],
                'export_label'   => 'Status',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'never',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'web_freigabe',
                'label'          => 'Für Web freigegeben',
                'typ'            => 'janein',
                'gruppe'         => 'Status',
                'import_alias'   => ['webfreigabe', 'webfreigegeben'],
                'export_label'   => 'Web-Freigabe',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'never',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
        ];

        return self::$cache;
    }

    /**
     * Keys aller per Formular und Import bearbeitbaren Felder
     * (fuer WerkRepository::bearbeitbareFelder()).
     * @return string[]
     */
    public static function bearbeitbareKeys(): array
    {
        return array_column(self::alle(), 'key');
    }

    /**
     * Keys aller numerisch zu exportierenden Felder (Export: als Zahl, nicht als String).
     * @return string[]
     */
    public static function zahlenKeys(): array
    {
        return array_column(
            array_filter(self::alle(), static fn(array $f): bool => in_array($f['typ'], ['zahl', 'jahr', 'geld'], true)),
            'key'
        );
    }

    /**
     * Fuer den Import-Dropdown: key => Anzeige-Label.
     * Reihenfolge: '' zuerst (ignorieren), dann alle Felder, dann bild_dateiname.
     * @return array<string, string>
     */
    public static function zielfelder(): array
    {
        $ergebnis = ['' => '– ignorieren –'];
        foreach (self::alle() as $feld) {
            $ergebnis[$feld['key']] = $feld['label'];
        }
        $ergebnis['bild_dateiname'] = 'Bild-Dateiname';
        for ($n = 2; $n <= 8; $n++) {
            $ergebnis["bild_dateiname_{$n}"] = "Bild-Dateiname {$n}";
        }
        return $ergebnis;
    }

    /**
     * Importierbare kunstwerke-DB-Felder (ohne bild_dateiname, das separat behandelt wird).
     * Fuer TabellenImport::felder() und den INSERT bei neuen Werken.
     * @return string[]
     */
    public static function felder(): array
    {
        return array_column(self::alle(), 'key');
    }

    /**
     * Auto-Erkennung beim Import: normalisierter Spaltenname => Feldkey.
     * @return array<string, string>
     */
    public static function autoErkennung(): array
    {
        $ergebnis = [];
        foreach (self::alle() as $feld) {
            foreach ($feld['import_alias'] as $alias) {
                $ergebnis[$alias] = $feld['key'];
            }
        }
        // bild_dateiname ist kein regulaeres Werkfeld, wird aber im Import behandelt.
        $ergebnis['dateiname']     = 'bild_dateiname';
        $ergebnis['bilddateiname'] = 'bild_dateiname';
        for ($n = 2; $n <= 8; $n++) {
            $ergebnis["dateiname{$n}"]     = "bild_dateiname_{$n}";
            $ergebnis["bilddateiname{$n}"] = "bild_dateiname_{$n}";
        }
        return $ergebnis;
    }

    /**
     * Exportierbare Felder in der historisch festgelegten Reihenfolge.
     * Neue Felder werden am Ende angehaengt (vor bild_dateiname), damit
     * alte Tabellenkalkulationen ohne neue Spalten weiterhin importiert
     * werden koennen.
     *
     * Historische Reihenfolge (v1.0):
     *   ort, maler, titel, format, technik, entstehungsjahr, ankaufjahr,
     *   ankauf, ankaufswert, wert, werktyp, status_farbe, bild_dateiname
     * Ab v1.1 am Ende angehaengt:
     *   beschreibung_oeffentlich, beschreibung_intern, herkunft, bildrechte,
     *   web_freigabe
     *
     * @return array<string, string> key => Spaltenüberschrift
     */
    public static function exportSpalten(): array
    {
        $index = array_column(self::alle(), null, 'key');
        $lbl   = static fn(string $k): string => $index[$k]['export_label'] ?? $k;

        return [
            // Historisch fixierte Reihenfolge
            'ort'                    => $lbl('ort'),
            'maler'                  => $lbl('maler'),
            'titel'                  => $lbl('titel'),
            'format'                 => $lbl('format'),
            'technik'                => $lbl('technik'),
            'entstehungsjahr'        => $lbl('entstehungsjahr'),
            'ankaufjahr'             => $lbl('ankaufjahr'),
            'ankauf'                 => $lbl('ankauf'),
            'ankaufswert'            => $lbl('ankaufswert'),
            'wert'                   => $lbl('wert'),
            'werktyp'                => $lbl('werktyp'),
            'status_farbe'           => $lbl('status_farbe'),
            'bild_dateiname'         => 'Dateiname',
            // Ab v1.1 angehaengt
            'beschreibung_oeffentlich' => $lbl('beschreibung_oeffentlich'),
            'beschreibung_intern'    => $lbl('beschreibung_intern'),
            'herkunft'               => $lbl('herkunft'),
            'copyright'              => $lbl('copyright'),
            'web_freigabe'           => $lbl('web_freigabe'),
            // Mehrere Bilder (Paket 4)
            'bild_dateiname_2'       => 'Dateiname 2',
            'bild_dateiname_3'       => 'Dateiname 3',
            'bild_dateiname_4'       => 'Dateiname 4',
            'bild_dateiname_5'       => 'Dateiname 5',
            'bild_dateiname_6'       => 'Dateiname 6',
            'bild_dateiname_7'       => 'Dateiname 7',
            'bild_dateiname_8'       => 'Dateiname 8',
        ];
    }
}
