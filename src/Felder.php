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
                'label'          => 'feld.ort',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['ort', 'standort', 'location'],
                'export_label'   => 'feld.ort.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'maler',
                'label'          => 'feld.maler',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['maler', 'kuenstler', 'kuenstlerin', 'artist'],
                'export_label'   => 'feld.maler.export',
                'in_detail'      => false, // wird im Etikett-Header angezeigt
                'in_pdf'         => false, // wird im PDF-Etikett angezeigt
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'titel',
                'label'          => 'feld.titel',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['titel', 'title'],
                'export_label'   => 'feld.titel.export',
                'in_detail'      => false, // wird im Etikett-Header angezeigt
                'in_pdf'         => false, // wird im PDF-Etikett angezeigt
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'format',
                'label'          => 'feld.format',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['format'],
                'export_label'   => 'feld.format.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'technik',
                'label'          => 'feld.technik',
                'typ'            => 'text',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['technik', 'technique'],
                'export_label'   => 'feld.technik.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'entstehungsjahr',
                'label'          => 'feld.entstehungsjahr',
                'typ'            => 'jahr',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['entstehungsjahr', 'enstehungsjahr', 'jahr', 'yearcreated', 'year'],
                'export_label'   => 'feld.entstehungsjahr.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'werktyp',
                'label'          => 'feld.werktyp',
                'typ'            => 'auswahl',
                'gruppe'         => 'Stammdaten',
                'import_alias'   => ['bild', 'werktyp', 'typ', 'type'],
                'export_label'   => 'feld.werktyp.export',
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
                'label'          => 'feld.ankaufjahr',
                'typ'            => 'jahr',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankaufjahr', 'ankaufsjahr', 'purchaseyear'],
                'export_label'   => 'feld.ankaufjahr.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'ankauf',
                'label'          => 'feld.ankauf',
                'typ'            => 'text',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankauf', 'gekauftvon', 'boughtfrom'],
                'export_label'   => 'feld.ankauf.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'ankaufswert',
                'label'          => 'feld.ankaufswert',
                'typ'            => 'geld',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['ankaufswert', 'ankaufwert', 'purchasevalue'],
                'export_label'   => 'feld.ankaufswert.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> false,
                'sensibel'       => true,
                'pflicht'        => false,
            ],
            [
                'key'            => 'wert',
                'label'          => 'feld.wert',
                'typ'            => 'geld',
                'gruppe'         => 'Ankauf',
                'import_alias'   => ['wert', 'preis', 'price'],
                'export_label'   => 'feld.wert.export',
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
                'label'          => 'feld.beschreibung_oeffentlich',
                'typ'            => 'langtext',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['beschreibungoeffentlich', 'beschreibung', 'descriptionpublic', 'description'],
                'export_label'   => 'feld.beschreibung_oeffentlich.export',
                'in_detail'      => true,
                'in_pdf'         => true,
                'oeffentlich'    => 'waehlbar',
                'oeffentlich_std'=> true,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'beschreibung_intern',
                'label'          => 'feld.beschreibung_intern',
                'typ'            => 'langtext',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['beschreibungintern', 'descriptioninternal', 'descriptionintern'],
                'export_label'   => 'feld.beschreibung_intern.export',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'never',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'copyright',
                'label'          => 'feld.copyright',
                'typ'            => 'text',
                'gruppe'         => 'Beschreibung',
                'import_alias'   => ['copyright', 'bildrechte', 'imagerights'],
                'export_label'   => 'feld.copyright.export',
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
                'label'          => 'feld.herkunft',
                'typ'            => 'text',
                'gruppe'         => 'Herkunft',
                'import_alias'   => ['herkunft', 'provenance'],
                'export_label'   => 'feld.herkunft.export',
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
                'label'          => 'feld.status_farbe',
                'typ'            => 'auswahl',
                'gruppe'         => 'Status',
                'import_alias'   => ['status', 'statusfarbe', 'statuszellfarbe'],
                'export_label'   => 'feld.status_farbe.export',
                'in_detail'      => true,
                'in_pdf'         => false,
                'oeffentlich'    => 'never',
                'oeffentlich_std'=> false,
                'sensibel'       => false,
                'pflicht'        => false,
            ],
            [
                'key'            => 'web_freigabe',
                'label'          => 'feld.web_freigabe',
                'typ'            => 'janein',
                'gruppe'         => 'Status',
                'import_alias'   => ['webfreigabe', 'webfreigegeben', 'webapproval', 'approvedforweb'],
                'export_label'   => 'feld.web_freigabe.export',
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
     * Übersetztes Label eines Feldes in der aktiven Sprache.
     * Fällt auf den t()-Key zurück, wenn er nicht gefunden wird.
     */
    public static function label(string $key): string
    {
        return t('feld.' . $key);
    }

    /**
     * Übersetztes Export-Label (ohne Einheit) in der aktiven Sprache.
     */
    public static function exportLabel(string $key): string
    {
        return t('feld.' . $key . '.export');
    }

    /**
     * Übersetzter Gruppenname der Felddefinition.
     */
    public static function gruppenLabel(string $gruppe): string
    {
        return t('feldgruppe.' . $gruppe);
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
     * Fuer den Import-Dropdown: key => Anzeige-Label (übersetzt).
     * Reihenfolge: '' zuerst (ignorieren), dann alle Felder, dann bild_dateiname.
     * @return array<string, string>
     */
    public static function zielfelder(): array
    {
        $ergebnis = ['' => t('feld.ignorieren')];
        foreach (self::alle() as $feld) {
            $ergebnis[$feld['key']] = self::label($feld['key']);
        }
        $ergebnis['bild_dateiname'] = t('feld.bild_dateiname');
        for ($n = 2; $n <= 8; $n++) {
            $ergebnis["bild_dateiname_{$n}"] = t('feld.bild_dateiname_n', ['n' => $n]);
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
        return [
            // Historisch fixierte Reihenfolge
            'ort'                    => self::exportLabel('ort'),
            'maler'                  => self::exportLabel('maler'),
            'titel'                  => self::exportLabel('titel'),
            'format'                 => self::exportLabel('format'),
            'technik'                => self::exportLabel('technik'),
            'entstehungsjahr'        => self::exportLabel('entstehungsjahr'),
            'ankaufjahr'             => self::exportLabel('ankaufjahr'),
            'ankauf'                 => self::exportLabel('ankauf'),
            'ankaufswert'            => self::exportLabel('ankaufswert'),
            'wert'                   => self::exportLabel('wert'),
            'werktyp'                => self::exportLabel('werktyp'),
            'status_farbe'           => self::exportLabel('status_farbe'),
            'bild_dateiname'         => t('feld.bild_dateiname.export'),
            // Ab v1.1 angehaengt
            'beschreibung_oeffentlich' => self::exportLabel('beschreibung_oeffentlich'),
            'beschreibung_intern'    => self::exportLabel('beschreibung_intern'),
            'herkunft'               => self::exportLabel('herkunft'),
            'copyright'              => self::exportLabel('copyright'),
            'web_freigabe'           => self::exportLabel('web_freigabe'),
            // Mehrere Bilder (Paket 4)
            'bild_dateiname_2'       => t('feld.bild_dateiname_n', ['n' => 2]),
            'bild_dateiname_3'       => t('feld.bild_dateiname_n', ['n' => 3]),
            'bild_dateiname_4'       => t('feld.bild_dateiname_n', ['n' => 4]),
            'bild_dateiname_5'       => t('feld.bild_dateiname_n', ['n' => 5]),
            'bild_dateiname_6'       => t('feld.bild_dateiname_n', ['n' => 6]),
            'bild_dateiname_7'       => t('feld.bild_dateiname_n', ['n' => 7]),
            'bild_dateiname_8'       => t('feld.bild_dateiname_n', ['n' => 8]),
        ];
    }
}
