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
            'sample_tenses' => 'array',
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
     * Does this tense need a card for every person? True only for drill-all-forms
     * verbs, never for the infinitive, and not for tenses the grid has switched to
     * "one sampled form" (the dash state).
     */
    public function drillsEveryForm(Tense|string $tense): bool
    {
        $value = $tense instanceof Tense ? $tense->value : $tense;

        return $this->drill_all_forms
            && $value !== Tense::Infinitive->value
            && ! in_array($value, $this->sample_tenses ?? [], true);
    }

    /** Does this verb permit generation in the given tense? */
    public function allows(Tense|string $tense): bool
    {
        $value = $tense instanceof Tense ? $tense->value : $tense;

        return in_array($value, $this->enabled_tenses ?? [], true);
    }
}
