<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TenantNote;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('keeps tenant rows isolated between two users', function (): void {
    $userA = TestUser::query()->create(['name' => 'User A']);
    $userB = TestUser::query()->create(['name' => 'User B']);

    $provisionA = TenantDatabase::provision($userA);
    $provisionB = TenantDatabase::provision($userB);

    expect($provisionA->created)->toBeTrue();
    expect($provisionB->created)->toBeTrue();

    TenantDatabase::run($userA, function (): void {
        Schema::connection('tenant')->create('notes', function ($table): void {
            $table->id();
            $table->string('name');
        });

        TenantNote::query()->create(['name' => 'A only']);
        expect(TenantNote::query()->count())->toBe(1);
    });

    TenantDatabase::run($userB, function (): void {
        Schema::connection('tenant')->create('notes', function ($table): void {
            $table->id();
            $table->string('name');
        });

        TenantNote::query()->create(['name' => 'B only']);
        expect(TenantNote::query()->count())->toBe(1);
        expect(TenantNote::query()->first()?->name)->toBe('B only');
    });

    TenantDatabase::run($userA, function (): void {
        expect(TenantNote::query()->count())->toBe(1);
        expect(TenantNote::query()->first()?->name)->toBe('A only');
    });
});

