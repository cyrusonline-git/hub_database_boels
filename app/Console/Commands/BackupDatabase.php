<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'boels:backup-database
                            {--retain=7 : Aantal dagen om backups te bewaren}';

    protected $description = 'Maakt een MySQL dump van de Boels CORE database in storage/backups/. Verwijdert backups ouder dan --retain dagen.';

    public function handle(): int
    {
        $cfg = config('database.connections.mysql');
        $timestamp = now()->format('Y-m-d_His');
        $filename = "boels_core_{$timestamp}.sql.gz";
        $backupDir = storage_path('backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fullPath = "{$backupDir}/{$filename}";

        $this->info("Backup starten van {$cfg['database']} ...");

        // Op shared hosting werkt mysqldump exec niet altijd. Daarom: pure PHP dump.
        $sql = $this->dumpWithPdo($cfg);

        $gz = gzopen($fullPath, 'w9');
        if (! $gz) {
            $this->error("Kon backup bestand niet aanmaken: $fullPath");
            return Command::FAILURE;
        }
        gzwrite($gz, $sql);
        gzclose($gz);

        $size = round(filesize($fullPath) / 1024, 1);
        $this->info("✓ Backup opgeslagen: {$filename} ({$size} KB)");

        // Oude backups opruimen
        $this->cleanupOldBackups($backupDir, (int) $this->option('retain'));

        return Command::SUCCESS;
    }

    private function dumpWithPdo(array $cfg): string
    {
        $pdo = DB::connection()->getPdo();
        $output = "-- Boels CORE database backup\n";
        $output .= "-- Date: " . now()->toDateTimeString() . "\n";
        $output .= "-- Database: {$cfg['database']}\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . $cfg['database'];

        foreach ($tables as $row) {
            $table = $row->$key;
            $output .= "-- ---------- Table: {$table} ----------\n";

            // Schema
            $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $create->{'Create Table'} . ";\n\n";

            // Data
            $rows = DB::select("SELECT * FROM `{$table}`");
            foreach ($rows as $r) {
                $vals = array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, (array) $r);
                $cols = array_keys((array) $r);
                $output .= "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
            }
            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    private function cleanupOldBackups(string $dir, int $retainDays): void
    {
        $cutoff = now()->subDays($retainDays)->timestamp;
        $deleted = 0;
        foreach (glob("{$dir}/boels_core_*.sql.gz") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $this->info("✓ {$deleted} oude backup(s) verwijderd (ouder dan {$retainDays} dagen).");
        }
    }
}
