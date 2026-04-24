<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use NexusScholar\LaravelTenantSqlite\Concerns\UsesTenantConnection;

class TenantNote extends Model
{
    use UsesTenantConnection;

    protected $table = 'notes';

    protected $guarded = [];

    public $timestamps = false;
}

