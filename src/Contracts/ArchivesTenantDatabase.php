<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\ArchiveResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface ArchivesTenantDatabase
{
    public function archive(TenantContext $context): ArchiveResult;
}

