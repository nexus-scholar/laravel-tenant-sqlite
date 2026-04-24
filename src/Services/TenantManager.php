<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use BadMethodCallException;
use NexusScholar\LaravelTenantSqlite\Contracts\ArchivesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\BacksUpTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\ActivatesTenantConnection;
use NexusScholar\LaravelTenantSqlite\Contracts\BuildsTenantDatabasePath;
use NexusScholar\LaravelTenantSqlite\Contracts\InspectsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\MigratesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\ProvisionsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\ResolvesTenant;
use NexusScholar\LaravelTenantSqlite\Contracts\PurgesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;
use NexusScholar\LaravelTenantSqlite\Support\ArchiveResult;
use NexusScholar\LaravelTenantSqlite\Support\BackupResult;
use NexusScholar\LaravelTenantSqlite\Support\InspectionResult;
use NexusScholar\LaravelTenantSqlite\Support\MigrationResult;
use NexusScholar\LaravelTenantSqlite\Support\ProvisionResult;
use NexusScholar\LaravelTenantSqlite\Support\PurgeResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

class TenantManager implements TenantDatabaseManager
{
    private ?TenantContext $currentTenant = null;

    public function __construct(
        private readonly ResolvesTenant $resolver,
        private readonly BuildsTenantDatabasePath $pathBuilder,
        private readonly ProvisionsTenantDatabase $provisioner,
        private readonly InspectsTenantDatabase $inspector,
        private readonly MigratesTenantDatabase $migrator,
        private readonly BacksUpTenantDatabase $backups,
        private readonly ArchivesTenantDatabase $archiver,
        private readonly PurgesTenantDatabase $purger,
        private readonly ActivatesTenantConnection $connectionManager,
    ) {
    }

    public function provision(mixed $tenant): ProvisionResult
    {
        $context = $this->resolveContext($tenant);

        return $this->provisioner->provision($context);
    }

    public function activate(mixed $tenant): TenantContext
    {
        $context = $this->resolveContext($tenant);
        $this->guardAgainstArchivedOrPurged($context->tenantKey);
        $this->connectionManager->activate($context);
        $this->currentTenant = $context;

        return $context;
    }

    public function deactivate(): void
    {
        $this->connectionManager->deactivate($this->currentTenant?->connectionName);
        $this->currentTenant = null;
    }

    public function migrate(mixed $tenant, bool $fresh = false, bool $seed = false): MigrationResult
    {
        $context = $this->resolveContext($tenant);

        if (! is_file($context->databasePath)) {
            $this->provisioner->provision($context);
        }

        return $this->run($context, fn () => $this->migrator->migrate($context, $fresh, $seed));
    }

    public function inspect(mixed $tenant): InspectionResult
    {
        return $this->inspector->inspect($this->resolveContext($tenant));
    }

    public function backup(mixed $tenant, ?string $name = null): BackupResult
    {
        return $this->backups->backup($this->resolveContext($tenant), $name);
    }

    public function archive(mixed $tenant): ArchiveResult
    {
        $context = $this->resolveContext($tenant);
        $this->deactivateIfCurrent($context->tenantKey);

        return $this->archiver->archive($context);
    }

    public function purge(mixed $tenant, bool $force = false): PurgeResult
    {
        $context = $this->resolveContext($tenant);
        $this->deactivateIfCurrent($context->tenantKey);

        return $this->purger->purge($context, $force);
    }

    public function path(mixed $tenant): string
    {
        return $this->resolveContext($tenant)->databasePath;
    }

    public function exists(mixed $tenant): bool
    {
        return is_file($this->path($tenant));
    }

    public function current(): ?TenantContext
    {
        return $this->currentTenant;
    }

    public function run(mixed $tenant, callable $callback): mixed
    {
        $previous = $this->currentTenant;
        $this->activate($tenant);

        try {
            return $callback();
        } finally {
            if ($previous !== null) {
                $this->connectionManager->activate($previous);
                $this->currentTenant = $previous;
            } else {
                $this->deactivate();
            }
        }
    }

    private function resolveContext(mixed $tenant): TenantContext
    {
        $context = $this->resolver->resolve($tenant);

        return new TenantContext(
            tenantKey: $context->tenantKey,
            ownerType: $context->ownerType,
            ownerId: $context->ownerId,
            databasePath: $this->pathBuilder->build($context->tenantKey),
            connectionName: $context->connectionName,
        );
    }

    private function deactivateIfCurrent(string $tenantKey): void
    {
        if ($this->currentTenant?->tenantKey === $tenantKey) {
            $this->deactivate();
        }
    }

    private function guardAgainstArchivedOrPurged(string $tenantKey): void
    {
        $status = \NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord::query()
            ->where('tenant_key', $tenantKey)
            ->value('status');

        if (in_array($status, ['archived', 'purged'], true)) {
            throw new \NexusScholar\LaravelTenantSqlite\Exceptions\TenantActivationFailed('Tenant database is archived or purged.');
        }
    }
}

