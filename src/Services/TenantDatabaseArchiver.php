<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Filesystem\Filesystem;
use NexusScholar\LaravelTenantSqlite\Contracts\ArchivesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantArchiveFailed;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\ArchiveResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use Throwable;

class TenantDatabaseArchiver implements ArchivesTenantDatabase
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function archive(TenantContext $context): ArchiveResult
    {
        $source = $context->databasePath;

        if (! $this->filesystem->exists($source)) {
            throw new TenantArchiveFailed('Cannot archive a missing tenant database.');
        }

        $path = $this->archivePath($context->tenantKey);
        $this->filesystem->ensureDirectoryExists(dirname($path));

        try {
            $this->filesystem->move($source, $path);

            $record = TenantDatabaseRecord::query()->where('tenant_key', $context->tenantKey)->first();
            if ($record !== null) {
                $meta = array_merge($record->meta ?? [], [
                    'archived_path' => $path,
                ]);

                $record->forceFill([
                    'status' => 'archived',
                    'archived_at' => now(),
                    'meta' => $meta,
                ])->save();
            }

            $result = new ArchiveResult($context, $path, true);

            event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseArchived($result));

            return $result;
        } catch (Throwable $e) {
            throw new TenantArchiveFailed('Failed to archive tenant database.', previous: $e);
        }
    }

    private function archivePath(string $tenantKey): string
    {
        $root = (string) config('tenant-database.archive.directory', storage_path('app/tenant-archives'));
        $timestamp = now()->format('Ymd_His');

        return rtrim($root, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $tenantKey
            . DIRECTORY_SEPARATOR
            . $timestamp
            . '-tenant.sqlite';
    }
}

