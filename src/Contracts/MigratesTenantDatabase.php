<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\MigrationResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface MigratesTenantDatabase
{
    public function migrate(TenantContext $context, bool $fresh = false, bool $seed = false): MigrationResult;
}

