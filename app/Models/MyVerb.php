<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyVerb extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unlocked' => 'boolean',
            'mastered' => 'boolean',
            'due' => 'date',
            'ease' => 'decimal:2',
            'last_reviewed' => 'datetime',
        ];
    }

    /** In training: unlocked and not yet marked as known. */
    public function scopeInTraining(Builder $query): Builder
    {
        return $query->where('unlocked', true)->where('mastered', false);
    }
}
