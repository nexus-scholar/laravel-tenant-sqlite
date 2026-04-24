<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\PurgeResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface PurgesTenantDatabase
{
    public function purge(TenantContext $context, bool $force = false): PurgeResult;
}

