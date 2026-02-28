<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class IssueActivity extends Model
{
    /** @use HasFactory<\Database\Factories\IssueActivityFactory> */
    use HasFactory;

    /** Disable updated_at — this table has no updated_at column. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'issue_id',
        'user_id',
        'action_type',
        'previous_status',
        'new_status',
        'comment',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /**
     * IssueActivity records are append-only — updates are forbidden.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('IssueActivity records are append-only and cannot be updated.');
        }

        return parent::save($options);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
