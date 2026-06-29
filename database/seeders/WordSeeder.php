<?php

namespace Database\Seeders;

use App\Models\Word;
use Illuminate\Database\Seeder;

class WordSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/words.json')), true);

        foreach ($rows as $row) {
            Word::updateOrCreate(
                ['spanish' => $row['spanish'], 'category' => $row['category']],
                [
                    'english' => $row['english'],
                    'gender' => $row['gender'],
                    'role' => $row['role'],
                    'unlocked' => $row['unlocked'],
                ],
            );
        }
    }
}
