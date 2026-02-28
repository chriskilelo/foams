<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    /** Disable updated_at — this table has no updated_at column. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * AuditLog records are immutable — updates are forbidden.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('AuditLog records are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
