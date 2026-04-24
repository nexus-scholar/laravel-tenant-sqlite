<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use Symfony\Component\HttpFoundation\Response;

class ActivateTenantDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user();

        if ($tenant !== null) {
            TenantDatabase::activate($tenant);
        }

        try {
            return $next($request);
        } finally {
            TenantDatabase::deactivate();
        }
    }
}
