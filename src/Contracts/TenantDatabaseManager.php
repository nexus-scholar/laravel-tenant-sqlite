<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\ArchiveResult;
use NexusScholar\LaravelTenantSqlite\Support\BackupResult;
use NexusScholar\LaravelTenantSqlite\Support\InspectionResult;
use NexusScholar\LaravelTenantSqlite\Support\MigrationResult;
use NexusScholar\LaravelTenantSqlite\Support\ProvisionResult;
use NexusScholar\LaravelTenantSqlite\Support\PurgeResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface TenantDatabaseManager
{
    public function provision(mixed $tenant): ProvisionResult;

    public function activate(mixed $tenant): TenantContext;

    public function deactivate(): void;

    public function migrate(mixed $tenant, bool $fresh = false, bool $seed = false): MigrationResult;

    public function inspect(mixed $tenant): InspectionResult;

    public function backup(mixed $tenant, ?string $name = null): BackupResult;

    public function archive(mixed $tenant): ArchiveResult;

    public function purge(mixed $tenant, bool $force = false): PurgeResult;

    public function path(mixed $tenant): string;

    public function exists(mixed $tenant): bool;

    public function current(): ?TenantContext;

    public function run(mixed $tenant, callable $callback): mixed;
}

