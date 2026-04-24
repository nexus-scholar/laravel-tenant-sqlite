<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('runs tenant migrations on the tenant connection and updates metadata', function (): void {
    $user = TestUser::query()->create(['name' => 'Migrated User']);

    $result = TenantDatabase::migrate($user);

    expect($result->ran)->toBeTrue()
        ->and($result->fresh)->toBeFalse()
        ->and($result->seed)->toBeFalse();

    TenantDatabase::run($user, function (): void {
        expect(Schema::connection('tenant')->hasTable('notes'))->toBeTrue();
    });

    expect(Schema::connection('testing')->hasTable('notes'))->toBeFalse();

    $record = TenantDatabaseRecord::query()->where('tenant_id', (string) $user->id)->first();

    expect($record)->not->toBeNull()
        ->and($record?->last_migrated_at)->not->toBeNull();
});

