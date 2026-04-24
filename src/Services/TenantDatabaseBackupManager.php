<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Filesystem\Filesystem;
use NexusScholar\LaravelTenantSqlite\Contracts\BacksUpTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantBackupFailed;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\BackupResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use ZipArchive;

class TenantDatabaseBackupManager implements BacksUpTenantDatabase
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function backup(TenantContext $context, ?string $name = null, bool $compress = false): BackupResult
    {
        $source = $context->databasePath;

        if (! $this->filesystem->exists($source)) {
            throw new TenantBackupFailed('Cannot back up a missing tenant database.');
        }

        $directory = $this->backupDirectory($context->tenantKey);
        $this->filesystem->ensureDirectoryExists($directory);

        $timestamp = now()->format('Ymd_His');
        $baseName = trim((string) $name) !== '' ? trim((string) $name) : 'tenant';

        if ($compress) {
            $path = $directory . DIRECTORY_SEPARATOR . $timestamp . '-' . $baseName . '.zip';
            $this->zipBackup($source, $path);
        } else {
            $path = $directory . DIRECTORY_SEPARATOR . $timestamp . '-' . $baseName . '.sqlite';
            $this->filesystem->copy($source, $path);
        }

        $record = TenantDatabaseRecord::query()->where('tenant_key', $context->tenantKey)->first();
        if ($record !== null) {
            $meta = array_merge($record->meta ?? [], [
                'last_backup_at' => now()->toIso8601String(),
                'last_backup_path' => $path,
            ]);

            $record->forceFill(['meta' => $meta])->save();
        }

        $result = new BackupResult($context, $path);

        event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseBackedUp($result));

        return $result;
    }

    private function backupDirectory(string $tenantKey): string
    {
        $root = (string) config('tenant-database.backup.directory', storage_path('app/tenant-backups'));

        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tenantKey;
    }

    private function zipBackup(string $source, string $target): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new TenantBackupFailed('ZIP compression is not available on this system.');
        }

        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new TenantBackupFailed('Failed to create backup archive.');
        }

        $zip->addFile($source, basename($source));
        $zip->close();
    }
}


