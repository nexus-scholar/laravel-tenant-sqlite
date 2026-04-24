<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Facades;

use Illuminate\Support\Facades\Facade;

class TenantDatabase extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tenant-database.manager';
    }
}

