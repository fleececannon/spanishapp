<?php

namespace App\Models;

use App\Enums\CardStatus;
use App\Enums\ReviewResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewState extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'due' => 'date',
            'last_reviewed' => 'datetime',
            'ease' => 'decimal:2',
            'last_result' => ReviewResult::class,
        ];
    }

    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * History for cards still in the deck. Retiring a card keeps its history so
     * progress survives if it comes back — but every count shown to a kid or a
     * grown-up must ignore it, or archived cards keep showing up as due.
     */
    public function scopeOnActiveCards(Builder $query): Builder
    {
        return $query->whereHas('card', fn (Builder $q) => $q->where('status', CardStatus::Active->value));
    }
}
