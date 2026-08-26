<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Transparent pilot-tier DB backup: mysqldump → gzip → storage/backups,
 * keeping the newest BACKUP_KEEP files. Runs on the daily scheduler.
 */
final class BackupDatabaseCommand extends Command
{
    protected $signature = 'app:backup-db {--keep=14 : Number of backups to retain}';

    protected $description = 'Dump the database to storage/backups (gzip) and prune old copies';

    public function handle(): int
    {
        $database = self::stringConfig('database.connections.mysql.database');
        $username = self::stringConfig('database.connections.mysql.username');
        $password = self::stringConfig('database.connections.mysql.password');
        $host = self::stringConfig('database.connections.mysql.host', '127.0.0.1');
        $port = self::stringConfig('database.connections.mysql.port', '3306');

        if ($database === '' || config('database.default') !== 'mysql') {
            $this->error('Backup requires the mysql connection.');

            return self::FAILURE;
        }

        $dir = storage_path('backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/db-'.now()->format('Y-m-d_His').'.sql.gz';

        $env = [];

        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $result = Process::env($env)
            ->timeout(600)
            ->run("mysqldump --host=$host --port=$port --user=$username --single-transaction --routines $database");

        $dump = $result->successful() ? $result->output() : '';

        if ($dump === '') {
            $this->error('Backup failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        file_put_contents($file, gzencode($dump, 6));

        $keep = max(1, (int) $this->option('keep'));
        $backups = glob($dir.'/db-*.sql.gz') ?: [];

        usort($backups, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        foreach (array_slice($backups, $keep) as $old) {
            @unlink($old);
        }

        $this->info('Backup written: '.basename($file).' ('.number_format(filesize($file) / 1024).' KB)');

        return self::SUCCESS;
    }

    private static function stringConfig(string $key, string $default = ''): string
    {
        $value = config($key, $default);

        return is_string($value) ? $value : $default;
    }
}
