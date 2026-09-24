<?php
declare(strict_types=1);

namespace App;

final class Backup
{
    /** @return array{dateiname: string, groesse: int} */
    public static function erstellen(bool $mitBildern, array $benutzer): array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        $pdo = Database::get();
        // Zufallsanteil: eindeutig auch bei zwei Backups pro Sekunde und nicht erratbar.
        $dateiname = 'backup_' . date('Y-m-d_His') . '_' . bin2hex(random_bytes(4))
            . ($mitBildern ? '' : '_nur-datenbank') . '.zip';
        $zielPfad = BACKUPS_PATH . '/' . $dateiname;

        // VACUUM INTO erzeugt einen konsistenten Schnappschuss, auch wenn parallel
        // geschrieben wird – eine einfache Dateikopie könnte halbfertig sein.
        $snapshot = DATA_PATH . '/snapshot_' . bin2hex(random_bytes(6)) . '.sqlite';
        try {
            $pdo->exec('VACUUM INTO ' . $pdo->quote($snapshot));
        } catch (\PDOException) {
            copy(DB_PATH, $snapshot);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zielPfad, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            @unlink($snapshot);
            throw new \RuntimeException('Backup-Datei konnte nicht angelegt werden: ' . $zielPfad);
        }
        $zip->addFile($snapshot, 'kunstverwaltung.sqlite');
        $zip->addFromString('LIESMICH.txt', self::liesmich($mitBildern));

        if ($mitBildern) {
            foreach (new \DirectoryIterator(BILDER_PATH) as $datei) {
                if (!$datei->isFile() || str_starts_with($datei->getFilename(), '.')) {
                    continue;
                }
                $name = 'bilder/' . $datei->getFilename();
                $zip->addFile($datei->getPathname(), $name);
                // Bilder sind bereits komprimiert: erneutes Komprimieren kostet nur Zeit.
                $zip->setCompressionName($name, \ZipArchive::CM_STORE);
            }
        }
        $zip->close();
        @unlink($snapshot);

        $groesse = (int) filesize($zielPfad);
        $pdo->prepare('INSERT INTO backups (dateiname, erstellt_von, dateigroesse, mit_bildern) VALUES (:d, :b, :g, :m)')
            ->execute(['d' => $dateiname, 'b' => $benutzer['id'], 'g' => $groesse, 'm' => $mitBildern ? 1 : 0]);

        return ['dateiname' => $dateiname, 'groesse' => $groesse];
    }

    public static function finde(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM backups WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $backup = $stmt->fetch();
        return $backup === false ? null : $backup;
    }

    public static function pfad(array $backup): string
    {
        return BACKUPS_PATH . '/' . basename($backup['dateiname']);
    }

    public static function loeschen(array $backup): void
    {
        @unlink(self::pfad($backup));
        Database::get()->prepare('DELETE FROM backups WHERE id = :id')->execute(['id' => $backup['id']]);
    }

    private static function liesmich(bool $mitBildern): string
    {
        return "Backup vom " . date('d.m.Y H:i') . "\r\n\r\n"
            . "Wiederherstellen (z. B. per FTP):\r\n"
            . "1. Die vorhandene Datei data/kv_*.sqlite vom Server herunterladen und dort löschen\r\n"
            . "   (es darf nur eine Datei kv_*.sqlite im Ordner data/ liegen).\r\n"
            . "2. kunstverwaltung.sqlite aus diesem ZIP in den Ordner data/ hochladen\r\n"
            . "   und in kv_<beliebige Buchstaben>.sqlite umbenennen (z. B. kv_wiederhergestellt.sqlite).\r\n"
            . ($mitBildern
                ? "3. Den Inhalt des Ordners bilder/ in den Ordner bilder/ auf dem Server hochladen.\r\n"
                : "3. Dieses Backup enthält keine Bilder – der Ordner bilder/ bleibt unverändert.\r\n");
    }
}
