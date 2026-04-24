<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Queue\Middleware;

use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;

class ActivateTenantForJob
{
    public function handle(mixed $job, callable $next): mixed
    {
        if (property_exists($job, 'tenantKey') && $job->tenantKey !== null) {
            TenantDatabase::activate($job->tenantKey);
        }

        try {
            return $next($job);
        } finally {
            TenantDatabase::deactivate();
        }
    }
}
