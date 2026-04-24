<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface ResolvesTenant
{
    public function resolve(mixed $tenant): TenantContext;
}

