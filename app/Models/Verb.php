<?php

namespace App\Models;

use App\Enums\Tense;
use App\Enums\VerbClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Verb extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'enabled_tenses' => 'array',
            'full_tenses' => 'array',
            'drill_all_forms' => 'boolean',
            'unlocked' => 'boolean',
            'vocab_card' => 'boolean',
            'verb_class' => VerbClass::class,
        ];
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('unlocked', true);
    }

    /**
     * Does this tense want a card for every person (the grid's check state)?
     * Any other enabled conjugated tense gets one sampled form (the dash).
     * The infinitive has no persons, so never.
     */
    public function drillsEveryForm(Tense|string $tense): bool
    {
        $value = $tense instanceof Tense ? $tense->value : $tense;

        return $value !== Tense::Infinitive->value
            && in_array($value, $this->full_tenses ?? [], true);
    }

    /** Does this verb permit generation in the given tense? */
    public function allows(Tense|string $tense): bool
    {
        $value = $tense instanceof Tense ? $tense->value : $tense;

        return in_array($value, $this->enabled_tenses ?? [], true);
    }
}
