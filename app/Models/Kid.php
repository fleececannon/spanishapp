<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Kid extends Authenticatable
{
    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'daily_new_card_pace' => 'integer',
        ];
    }

    public function reviewStates(): HasMany
    {
        return $this->hasMany(ReviewState::class);
    }
}
