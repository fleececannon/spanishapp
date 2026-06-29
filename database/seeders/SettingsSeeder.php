<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put('house_style', <<<'TXT'
        Build short, natural, everyday sentences a child can read and relate to. Favor comprehension over complexity. Keep sentences concrete and friendly.

        When grading a kid's English translation, be encouraging and forgiving: ignore spelling, accents, synonyms, and word order, and accept natural paraphrase and idiomatic readings. Only require that they got the overall meaning right, plus the meaning-critical features the card calls out (tense, gender, number/person). The goal is comfort reading and understanding Spanish, not precision.
        TXT);

        Setting::put('srs_tuning', [
            'starting_interval' => 1,
            'starting_ease' => 2.5,
            'miss_penalty' => 0.2,
            'min_ease' => 1.3,
        ]);
    }
}
