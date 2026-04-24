<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

interface BuildsTenantDatabasePath
{
    public function build(string $tenantKey): string;
}

