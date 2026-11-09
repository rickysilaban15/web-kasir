<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    protected $fillable = [
        'license_id',
        'device_installation_id',
        'device_name',
        'device_model',
        'os_version',
        'app_version',
        'status',
        'activated_at',
        'last_validated_at',
        'deactivated_at',
        'last_ip_address',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}

