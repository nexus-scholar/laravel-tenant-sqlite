<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Exceptions\TenantActivationFailed;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('prevents activating an archived tenant database', function (): void {
    $user = TestUser::query()->create(['name' => 'Archived Tenant']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);
    TenantDatabase::archive($user);

    expect(fn () => TenantDatabase::activate($user))
        ->toThrow(TenantActivationFailed::class, 'archived or purged');
});

it('prevents activating a purged tenant database', function (): void {
    $user = TestUser::query()->create(['name' => 'Purged Tenant']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);
    TenantDatabase::archive($user);
    TenantDatabase::purge($user, true);

    expect(fn () => TenantDatabase::activate($user))
        ->toThrow(TenantActivationFailed::class, 'archived or purged');
});

