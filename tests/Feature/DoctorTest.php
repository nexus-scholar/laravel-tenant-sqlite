<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseDoctor;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('reports diagnostics with a config warning when the config file is not published', function (): void {
    File::ensureDirectoryExists(config('tenant-database.base_path'));

    $this->artisan('tenant-database:doctor')
        ->assertExitCode(0)
        ->expectsOutputToContain('SQLite driver: ok')
        ->expectsOutputToContain('Base path: ok')
        ->expectsOutputToContain('Migration paths: ok')
        ->expectsOutputToContain('Config file: warn')
        ->expectsOutputToContain('Metadata table: ok');
});

it('flags a missing base path as a failure', function (): void {
    config()->set('tenant-database.base_path', __DIR__ . '/temp/missing-doctor-base-path');

    $result = app(TenantDatabaseDoctor::class)->diagnose();

    $basePathCheck = collect($result->checks)->firstWhere('label', 'Base path');

    expect($result->hasFailures())->toBeTrue()
        ->and($basePathCheck?->status)->toBe('fail')
        ->and($basePathCheck?->message)->toContain('does not exist');
});

it('flags a missing metadata table as a failure', function (): void {
    Schema::dropIfExists('tenant_databases');

    $result = app(TenantDatabaseDoctor::class)->diagnose();

    $metadataCheck = collect($result->checks)->firstWhere('label', 'Metadata table');

    expect($result->hasFailures())->toBeTrue()
        ->and($metadataCheck?->status)->toBe('fail')
        ->and($metadataCheck?->message)->toContain('tenant_databases is missing');
});

