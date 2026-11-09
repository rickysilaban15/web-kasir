<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'platform',
        'version_name',
        'version_code',
        'min_supported_version_code',
        'is_force_update',
        'release_notes',
        'download_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'version_code' => 'integer',
            'min_supported_version_code' => 'integer',
            'is_force_update' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}

