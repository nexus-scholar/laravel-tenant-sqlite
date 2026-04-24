<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Filesystem\Filesystem;
use NexusScholar\LaravelTenantSqlite\Contracts\ProvisionsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantProvisioningFailed;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\ProvisionResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use PDO;
use Throwable;

class TenantDatabaseProvisioner implements ProvisionsTenantDatabase
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function provision(TenantContext $context): ProvisionResult
    {
        $path = $context->databasePath;
        $dir = dirname($path);
        $created = false;

        try {
            if (! $this->filesystem->exists($dir)) {
                $this->filesystem->makeDirectory($dir, 0755, true);
            }

            if (! $this->filesystem->exists($path)) {
                $this->filesystem->put($path, '');
                $created = true;
            }

            $this->applyPragmas($path);
            $this->upsertMetadata($context);

            $result = new ProvisionResult(
                tenant: $context,
                path: $path,
                created: $created,
                bootstrapped: false,
                schemaVersion: null,
            );

            event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseCreated($result));

            return $result;
        } catch (Throwable $e) {
            throw new TenantProvisioningFailed('Failed to provision tenant database.', previous: $e);
        }
    }

    private function applyPragmas(string $path): void
    {
        $pragmas = (array) config('tenant-database.pragmas', []);
        $pdo = new PDO('sqlite:' . $path);

        foreach ($pragmas as $name => $value) {
            $normalized = is_bool($value) ? ($value ? 'ON' : 'OFF') : (string) $value;
            $pdo->exec(sprintf('PRAGMA %s = %s;', $name, $pdo->quote($normalized)));
        }
    }

    private function upsertMetadata(TenantContext $context): void
    {
        TenantDatabaseRecord::query()->updateOrCreate(
            [
                'tenant_key' => $context->tenantKey,
            ],
            [
                'tenant_type' => $context->ownerType,
                'tenant_id' => (string) $context->ownerId,
                'driver' => 'sqlite',
                'database_path' => $context->databasePath,
                'status' => 'active',
            ],
        );
    }
}

