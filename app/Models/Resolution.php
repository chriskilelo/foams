<?php

namespace App\Models;

use App\Enums\ResolutionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resolution extends Model
{
    /** @use HasFactory<\Database\Factories\ResolutionFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'root_cause',
        'steps_taken',
        'resolution_type',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'steps_taken' => 'array',
            'resolution_type' => ResolutionType::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
