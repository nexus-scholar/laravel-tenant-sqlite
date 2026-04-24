<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface ActivatesTenantConnection
{
    public function activate(TenantContext $context): void;

    public function deactivate(?string $connectionName = null): void;
}

