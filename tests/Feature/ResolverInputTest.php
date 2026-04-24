<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('resolves tenant from numeric string owner id input', function (): void {
    $user = TestUser::query()->create(['name' => 'Numeric String Resolver']);

    $provision = TenantDatabase::provision($user);
    $activated = TenantDatabase::activate((string) $user->id);

    expect($activated->tenantKey)->toBe($provision->tenant->tenantKey);

    TenantDatabase::deactivate();
});

it('accepts TenantContext input directly', function (): void {
    $user = TestUser::query()->create(['name' => 'Context Resolver']);

    $provision = TenantDatabase::provision($user);
    $context = new TenantContext(
        tenantKey: $provision->tenant->tenantKey,
        ownerType: $provision->tenant->ownerType,
        ownerId: $provision->tenant->ownerId,
        databasePath: $provision->tenant->databasePath,
        connectionName: $provision->tenant->connectionName,
    );

    $activated = TenantDatabase::activate($context);

    expect($activated->tenantKey)->toBe($provision->tenant->tenantKey);

    TenantDatabase::deactivate();
});

it('rejects unsupported tenant input', function (): void {
    expect(fn () => TenantDatabase::activate(new stdClass()))
        ->toThrow(InvalidArgumentException::class, 'Unsupported tenant input');
});

