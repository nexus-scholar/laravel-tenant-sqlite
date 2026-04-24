<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('creates a backup artifact for a tenant database', function (): void {
    $user = TestUser::query()->create(['name' => 'Backup User']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $result = TenantDatabase::backup($user, 'snapshot');

    expect($result->path)->toEndWith('.sqlite')
        ->and(File::exists($result->path))->toBeTrue();
});

it('prints backup output from the artisan command', function (): void {
    $user = TestUser::query()->create(['name' => 'Backup Command User']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $this->artisan('tenant-database:backup', [
        'tenant' => (string) $user->getKey(),
        '--name' => 'command-snapshot',
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Tenant key:')
        ->expectsOutputToContain('Backup path:');
});

