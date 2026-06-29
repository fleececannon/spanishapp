<?php

namespace App\Models;

use App\Enums\WordCategory;
use App\Enums\WordRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unlocked' => 'boolean',
            'category' => WordCategory::class,
            'role' => WordRole::class,
        ];
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('unlocked', true);
    }

    public function scopeTargets(Builder $query): Builder
    {
        return $query->where('role', WordRole::Target->value);
    }

    public function scopeIngredients(Builder $query): Builder
    {
        return $query->where('role', WordRole::Ingredient->value);
    }

    /** Live count for the admin "keep the pantry lean" badge. */
    public static function ingredientNounCount(): int
    {
        return static::query()
            ->where('category', WordCategory::Noun->value)
            ->where('role', WordRole::Ingredient->value)
            ->count();
    }
}
