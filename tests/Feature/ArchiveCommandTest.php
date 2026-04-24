<?php

declare(strict_types=1);

use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('prints archive output from the artisan command', function (): void {
    $user = TestUser::query()->create(['name' => 'Archive Command User']);

    \NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase::provision($user);
    \NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase::migrate($user);

    $this->artisan('tenant-database:archive', [
        'tenant' => (string) $user->getKey(),
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Tenant key:')
        ->expectsOutputToContain('Archive path:');
});

