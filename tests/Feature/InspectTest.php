<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('inspects a tenant database after provisioning and migration', function (): void {
    $user = TestUser::query()->create(['name' => 'Inspector']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $result = TenantDatabase::inspect($user);

    expect($result->exists)->toBeTrue()
        ->and($result->writable)->toBeTrue()
        ->and($result->tables)->toContain('notes')
        ->and($result->schemaVersion)->toBeNull()
        ->and($result->pragmas)->toHaveKey('foreign_keys');
});

it('prints inspect output from the artisan command', function (): void {
    $user = TestUser::query()->create(['name' => 'Command Inspector']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $this->artisan('tenant-database:inspect', [
        'tenant' => (string) $user->getKey(),
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Tenant key:')
        ->expectsOutputToContain('Status: active')
        ->expectsOutputToContain('Path:')
        ->expectsOutputToContain('Size bytes:')
        ->expectsOutputToContain('Schema version:')
        ->expectsOutputToContain('Tables');
});

