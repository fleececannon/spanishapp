<?php

namespace Database\Seeders;

use App\Models\Verb;
use Illuminate\Database\Seeder;

class VerbSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/verbs.json')), true);

        foreach ($rows as $row) {
            Verb::updateOrCreate(
                ['spanish' => $row['spanish'], 'tag' => $row['tag']],
                [
                    'english' => $row['english'],
                    'verb_class' => $row['verb_class'],
                    'enabled_tenses' => $row['enabled_tenses'],
                    'drill_all_forms' => $row['drill_all_forms'],
                    // Key verbs start with every enabled conjugated tense at "every person".
                    'full_tenses' => $row['drill_all_forms']
                        ? array_values(array_diff($row['enabled_tenses'], ['infinitive']))
                        : [],
                    'unlocked' => $row['unlocked'],
                ],
            );
        }
    }
}
