<?php

namespace App\Models;

use App\Enums\AssetLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatusLog extends Model
{
    /** @use HasFactory<\Database\Factories\AssetStatusLogFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'logged_date',
        'observed_at',
        'status',
        'throughput_mbps',
        'remarks',
        'latitude',
        'longitude',
        'is_amendment',
        'amendment_reason',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetLogStatus::class,
            'logged_date' => 'date',
            'synced_at' => 'datetime',
            'is_amendment' => 'boolean',
            'throughput_mbps' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
