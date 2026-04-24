<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\BackupResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface BacksUpTenantDatabase
{
    public function backup(TenantContext $context, ?string $name = null, bool $compress = false): BackupResult;
}

