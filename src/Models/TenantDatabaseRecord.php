<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDatabaseRecord extends Model
{
    protected $table = 'tenant_databases';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'size_bytes' => 'integer',
        'last_migrated_at' => 'datetime',
        'archived_at' => 'datetime',
        'purged_at' => 'datetime',
    ];
}

