<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_databases', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_type')->nullable();
            $table->string('tenant_id');
            $table->string('tenant_key')->unique();
            $table->string('driver')->default('sqlite');
            $table->string('database_path');
            $table->string('status')->default('pending');
            $table->string('schema_version')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('last_migrated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_type', 'tenant_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_databases');
    }
};

