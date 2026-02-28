<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    /** @use HasFactory<\Database\Factories\AssetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'type',
        'county_id',
        'location_name',
        'latitude',
        'longitude',
        'assigned_to',
        'installation_date',
        'manufacturer',
        'model',
        'serial_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'status' => AssetStatus::class,
            'installation_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(AssetStatusLog::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
