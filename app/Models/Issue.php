<?php

namespace App\Models;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\ReporterCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Issue extends Model
{
    /** @use HasFactory<\Database\Factories\IssueFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'asset_id',
        'county_id',
        'issue_type',
        'severity',
        'status',
        'reporter_category',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'created_by_user_id',
        'assigned_to_user_id',
        'description',
        'workaround_applied',
        'duplicate_of_id',
        'acknowledged_at',
        'resolved_at',
        'closed_at',
        'sla_due_at',
        'sla_breached',
        'is_escalated',
        'escalated_at',
        'escalated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'severity' => IssueSeverity::class,
            'status' => IssueStatus::class,
            'reporter_category' => ReporterCategory::class,
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'escalated_at' => 'datetime',
            'sla_breached' => 'boolean',
            'is_escalated' => 'boolean',
            'workaround_applied' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'duplicate_of_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class);
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(Resolution::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
