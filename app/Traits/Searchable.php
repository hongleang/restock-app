<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearchBy(Builder $query, mixed $search = '', array $fields = []): Builder
    {
        if (!filled($search)) {
            return $query;
        }

        $search = trim((string)$search);
        $term = '%' . addcslashes($search, '%_') . '%';

        return $query->where(function ($query) use ($term, $fields){
            foreach ($fields as $field) {
                $query->orWhere($field, 'like', $term);
            }
        });
    }
}
