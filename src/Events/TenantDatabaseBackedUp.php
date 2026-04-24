<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\BackupResult;

class TenantDatabaseBackedUp
{
    public function __construct(public readonly BackupResult $result)
    {
    }
}
