<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    /** @use HasFactory<\Database\Factories\CountyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'region_id',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
