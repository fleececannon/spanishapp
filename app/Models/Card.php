<?php

namespace App\Models;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'uses_concepts' => 'array',
            'must_match' => 'array',
            'source' => CardSource::class,
            'status' => CardStatus::class,
        ];
    }

    public function reviewStates(): HasMany
    {
        return $this->hasMany(ReviewState::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CardStatus::Active->value);
    }
}
