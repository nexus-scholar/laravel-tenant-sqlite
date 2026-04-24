<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\PurgeResult;

class TenantDatabasePurged
{
    public function __construct(public readonly PurgeResult $result)
    {
    }
}
