<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'user_id',
        'package_id',
        'status',
        'max_devices',
        'duration_days',
        'offline_grace_days',
        'activated_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'max_devices' => 'integer',
            'duration_days' => 'integer',
            'offline_grace_days' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function activeActivations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class)->where('status', 'active');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked' || $this->revoked_at !== null;
    }

    public function maskedKey(): string
    {
        $parts = explode('-', $this->license_key);
        if (count($parts) === 4) {
            return "{$parts[0]}-{$parts[1]}-****-{$parts[3]}";
        }
        return substr($this->license_key, 0, 6).'****';
    }
}

