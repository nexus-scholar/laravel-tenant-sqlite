<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\ProvisionResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface ProvisionsTenantDatabase
{
    public function provision(TenantContext $context): ProvisionResult;
}

