<?php

namespace App\Models;

use App\Enums\ReviewResult;
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
}
