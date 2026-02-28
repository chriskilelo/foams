<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class RegionScope implements Scope
{
    public function __construct(private readonly int $regionId) {}

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Both Asset and Issue have a county_id FK; counties belong to a region.
     * We filter via a whereHas on the county relationship.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('county', fn (Builder $q) => $q->where('region_id', $this->regionId));
    }
}
