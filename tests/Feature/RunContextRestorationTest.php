<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('restores empty context after run callback throws', function (): void {
    $user = TestUser::query()->create(['name' => 'Run Failure']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    expect(TenantDatabase::current())->toBeNull();

    expect(fn () => TenantDatabase::run($user, function (): void {
            throw new RuntimeException('boom');
        }))
        ->toThrow(RuntimeException::class, 'boom');

    expect(TenantDatabase::current())->toBeNull();
});

it('restores previous tenant context after nested run', function (): void {
    $userA = TestUser::query()->create(['name' => 'Outer Tenant']);
    $userB = TestUser::query()->create(['name' => 'Inner Tenant']);

    TenantDatabase::provision($userA);
    TenantDatabase::migrate($userA);
    TenantDatabase::provision($userB);
    TenantDatabase::migrate($userB);

    $outer = TenantDatabase::activate($userA);
    $innerKey = TenantDatabase::path($userB);

    TenantDatabase::run($userB, function () use ($innerKey): void {
        expect(TenantDatabase::current())->not->toBeNull()
            ->and(TenantDatabase::current()?->databasePath)->toBe($innerKey);
    });

    expect(TenantDatabase::current())->not->toBeNull()
        ->and(TenantDatabase::current()?->tenantKey)->toBe($outer->tenantKey);

    TenantDatabase::deactivate();
    expect(TenantDatabase::current())->toBeNull();
});


