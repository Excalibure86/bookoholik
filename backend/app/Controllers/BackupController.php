<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Backup Controller
 * Manual backup management
 */
class BackupController extends BaseController
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * POST /api/backup/create
     * Trigger a manual database backup
     */
    public function create(array $params): void
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $dbname = $_ENV['DB_DATABASE'] ?? 'home_library';
        $username = $_ENV['DB_USERNAME'] ?? 'library_user';
        $password = $_ENV['DB_PASSWORD'] ?? 'library_secret';

        $timestamp = date('Ymd_His');
        $filename = "manual_backup_{$timestamp}.sql";
        $filepath = "{$this->backupDir}/{$filename}";

        // Set password in environment for pg_dump
        putenv("PGPASSWORD=" . $password);

        // Use escapeshellarg to prevent command injection
        $command = sprintf(
            'pg_dump -h %s -p %s -U %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($dbname),
            escapeshellarg($filepath)
        );
        exec($command, $output, $returnCode);

        // Clear password from environment
        putenv('PGPASSWORD');

        if ($returnCode !== 0) {
            $this->json(['error' => 'Backup failed.', 'details' => implode("\n", $output)], 500);
            return;
        }

        // Compress the backup
        $gzFilepath = $filepath . '.gz';
        $fp = fopen($filepath, 'rb');
        $gz = gzopen($gzFilepath, 'wb9');
        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 1024 * 512));
        }
        gzclose($gz);
        fclose($fp);
        unlink($filepath);

        $this->json([
            'message' => 'Backup created successfully.',
            'data' => [
                'filename' => $filename . '.gz',
                'size' => filesize($gzFilepath),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * GET /api/backup/list
     * List available backups
     */
    public function list(array $params): void
    {
        $files = glob("{$this->backupDir}/*.sql.gz");
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Sort by most recent first
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $this->json(['data' => $backups]);
    }

    /**
     * GET /api/backup/download/{filename}
     * Download a backup file
     */
    public function download(array $params): void
    {
        $filename = basename($params['filename']); // Prevent directory traversal
        $filepath = "{$this->backupDir}/{$filename}";

        if (!file_exists($filepath)) {
            $this->json(['error' => 'Backup file not found.'], 404);
            return;
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }

    /**
     * DELETE /api/backup/{filename}
     */
    public function destroy(array $params): void
    {
        $filename = basename($params['filename']);
        $filepath = "{$this->backupDir}/{$filename}";

        if (!file_exists($filepath)) {
            $this->json(['error' => 'Backup file not found.'], 404);
            return;
        }

        unlink($filepath);
        $this->json(['message' => 'Backup deleted successfully.']);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}
