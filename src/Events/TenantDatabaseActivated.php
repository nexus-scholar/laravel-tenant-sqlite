<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

class TenantDatabaseActivated
{
    public function __construct(public readonly TenantContext $context)
    {
    }
}
