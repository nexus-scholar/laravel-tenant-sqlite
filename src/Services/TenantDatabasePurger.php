<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Filesystem\Filesystem;
use NexusScholar\LaravelTenantSqlite\Contracts\PurgesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantPurgeFailed;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\PurgeResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use Throwable;

class TenantDatabasePurger implements PurgesTenantDatabase
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function purge(TenantContext $context, bool $force = false): PurgeResult
    {
        if (! $force) {
            throw new TenantPurgeFailed('Purge requires the --force flag.');
        }

        $record = TenantDatabaseRecord::query()->where('tenant_key', $context->tenantKey)->first();
        $path = $context->databasePath;
        if ($record !== null && isset($record->meta['archived_path'])) {
            $path = (string) $record->meta['archived_path'];
        }

        if (! $this->filesystem->exists($path)) {
            throw new TenantPurgeFailed('Cannot purge a missing tenant database file.');
        }

        try {
            $this->filesystem->delete($path);

            if ($record !== null) {
                $meta = array_merge($record->meta ?? [], [
                    'purged_path' => $path,
                ]);

                $record->forceFill([
                    'status' => 'purged',
                    'purged_at' => now(),
                    'meta' => $meta,
                ])->save();
            }

            $result = new PurgeResult($context, $path, true);

            event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabasePurged($result));

            return $result;
        } catch (Throwable $e) {
            throw new TenantPurgeFailed('Failed to purge tenant database.', previous: $e);
        }
    }
}

