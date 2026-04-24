<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Contracts;

use NexusScholar\LaravelTenantSqlite\Support\InspectionResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

interface InspectsTenantDatabase
{
    public function inspect(TenantContext $context): InspectionResult;
}

