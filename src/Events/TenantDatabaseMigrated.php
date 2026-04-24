<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\MigrationResult;

class TenantDatabaseMigrated
{
    public function __construct(public readonly MigrationResult $result)
    {
    }
}
