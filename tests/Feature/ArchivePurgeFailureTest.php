<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Exceptions\TenantArchiveFailed;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('fails archive command when tenant database file is already moved', function (): void {
    $user = TestUser::query()->create(['name' => 'Archive Missing Source']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);
    TenantDatabase::archive($user);

    expect(fn () => $this->artisan('tenant-database:archive', [
        'tenant' => (string) $user->getKey(),
    ]))->toThrow(TenantArchiveFailed::class, 'missing tenant database');
});

it('fails purge command with force when the tenant file is already purged', function (): void {
    $user = TestUser::query()->create(['name' => 'Double Purge']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);
    TenantDatabase::archive($user);
    TenantDatabase::purge($user, true);

    $this->artisan('tenant-database:purge', [
        'tenant' => (string) $user->getKey(),
        '--force' => true,
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('missing tenant database file');
});


