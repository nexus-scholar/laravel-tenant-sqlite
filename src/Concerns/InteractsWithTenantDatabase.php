<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Concerns;

trait InteractsWithTenantDatabase
{
    public string|int|null $tenantKey = null;
}
