<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Concerns;

trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        return config('tenant-database.connection_name', 'tenant');
    }
}

