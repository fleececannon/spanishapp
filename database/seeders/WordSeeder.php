<?php

namespace Database\Seeders;

use App\Models\Word;
use App\Services\Vocab\VocabCardService;
use Illuminate\Database\Seeder;

class WordSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/words.json')), true);

        foreach ($rows as $row) {
            $values = [
                'english' => $row['english'],
                'gender' => $row['gender'],
                'role' => $row['role'],
                'unlocked' => $row['unlocked'],
            ];

            // Only rows that declare vocab_card set it — silence leaves the
            // admin's checkbox choices alone on re-seed.
            if (array_key_exists('vocab_card', $row)) {
                $values['vocab_card'] = $row['vocab_card'];
            }

            Word::updateOrCreate(
                ['spanish' => $row['spanish'], 'category' => $row['category']],
                $values,
            );
        }

        app(VocabCardService::class)->resyncAll();
    }
}
