<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\ProvisionResult;

class TenantDatabaseCreated
{
    public function __construct(public readonly ProvisionResult $result)
    {
    }
}
