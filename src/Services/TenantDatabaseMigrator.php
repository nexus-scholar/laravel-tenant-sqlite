<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Contracts\Console\Kernel;
use NexusScholar\LaravelTenantSqlite\Contracts\MigratesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantMigrationFailed;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\MigrationResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use Throwable;

class TenantDatabaseMigrator implements MigratesTenantDatabase
{
    public function __construct(private readonly Kernel $artisan)
    {
    }

    public function migrate(TenantContext $context, bool $fresh = false, bool $seed = false): MigrationResult
    {
        $paths = (array) config('tenant-database.tenant_migration_paths', []);

        try {
            $command = $fresh ? 'migrate:fresh' : 'migrate';

            $exitCode = $this->artisan->call($command, [
                '--database' => $context->connectionName,
                '--path' => $paths,
                '--realpath' => true,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                throw new TenantMigrationFailed('Tenant migration command failed.');
            }

            if ($seed) {
                $seedCode = $this->artisan->call('db:seed', [
                    '--database' => $context->connectionName,
                    '--force' => true,
                ]);

                if ($seedCode !== 0) {
                    throw new TenantMigrationFailed('Tenant seed command failed.');
                }
            }

            TenantDatabaseRecord::query()->where('tenant_key', $context->tenantKey)->update([
                'last_migrated_at' => now(),
                'status' => 'active',
            ]);

            $result = new MigrationResult(
                tenant: $context,
                ran: true,
                fresh: $fresh,
                seed: $seed,
            );

            event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseMigrated($result));

            return $result;
        } catch (Throwable $e) {
            if ($e instanceof TenantMigrationFailed) {
                throw $e;
            }

            throw new TenantMigrationFailed('Failed while migrating tenant database.', previous: $e);
        }
    }
}

