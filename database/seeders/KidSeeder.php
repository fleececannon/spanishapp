<?php

namespace Database\Seeders;

use App\Models\Kid;
use Illuminate\Database\Seeder;

class KidSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Kai', 'password' => '053018'],
            ['name' => 'Malick', 'password' => '080216'],
        ] as $kid) {
            Kid::updateOrCreate(
                ['name' => $kid['name']],
                ['password' => $kid['password'], 'daily_new_card_pace' => 5],
            );
        }
    }
}
