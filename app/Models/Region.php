<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    /** @use HasFactory<\Database\Factories\RegionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function counties(): HasMany
    {
        return $this->hasMany(County::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
