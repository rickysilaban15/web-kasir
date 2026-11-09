<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'max_devices',
        'default_duration_days',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_devices' => 'integer',
            'default_duration_days' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'package_features')
            ->withTimestamps();
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}

