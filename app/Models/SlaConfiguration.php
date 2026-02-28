<?php

namespace App\Models;

use App\Enums\IssueSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\SlaConfigurationFactory> */
    use HasFactory;

    protected $fillable = [
        'severity',
        'acknowledge_within_hrs',
        'resolve_within_hrs',
        'effective_from',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'severity' => IssueSeverity::class,
            'effective_from' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
