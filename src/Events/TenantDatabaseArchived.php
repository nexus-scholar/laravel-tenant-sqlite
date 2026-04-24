<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Events;

use NexusScholar\LaravelTenantSqlite\Support\ArchiveResult;

class TenantDatabaseArchived
{
    public function __construct(public readonly ArchiveResult $result)
    {
    }
}
