<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds tenant package details to the about command', function (): void {
    $this->artisan('about')
        ->assertExitCode(0)
        ->expectsOutputToContain('Tenant SQLite')
        ->expectsOutputToContain('Connection')
        ->expectsOutputToContain('Base path')
        ->expectsOutputToContain('Resolver')
        ->expectsOutputToContain('Metadata records');
});

it('keeps about output resilient when metadata table is missing', function (): void {
    Schema::dropIfExists('tenant_databases');

    $this->artisan('about')
        ->assertExitCode(0)
        ->expectsOutputToContain('Tenant SQLite')
        ->expectsOutputToContain('Metadata records')
        ->expectsOutputToContain('n/a');
});

