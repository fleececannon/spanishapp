<?php

namespace Database\Seeders;

use App\Models\Pattern;
use Illuminate\Database\Seeder;

class PatternSeeder extends Seeder
{
    public function run(): void
    {
        $patterns = [
            [
                'name' => 'Direct-object pronoun placement',
                'instruction' => "Use direct-object pronouns (lo, la, le, los, las...). Prefer them before the verb, e.g. \"lo quiero\", or attached to an infinitive, e.g. \"quiero verlo\".",
                'enabled' => false,
            ],
            [
                'name' => 'Negation',
                'instruction' => 'You may form negative sentences with "no" before the verb, and use nunca / tampoco where natural.',
                'enabled' => false,
            ],
            [
                'name' => 'Adjective agreement',
                'instruction' => 'Adjectives must agree with the noun in gender and number.',
                'enabled' => false,
            ],
            [
                'name' => 'Questions',
                'instruction' => 'You may form simple questions using ¿...? and question words that are unlocked.',
                'enabled' => false,
            ],
        ];

        foreach ($patterns as $pattern) {
            Pattern::updateOrCreate(['name' => $pattern['name']], $pattern);
        }
    }
}
