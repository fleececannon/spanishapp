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
                'instruction' => "Use direct-object pronouns (lo, la, los, las, me, te, nos) when natural: before a conjugated verb, e.g. \"Lo tengo\" or \"Te veo\", or attached to the end of an infinitive, e.g. \"Quiero aprenderlo\". lo/la/los/las must agree in gender and number with the noun they replace, and only replace nouns that are unlocked.",
                'enabled' => true,
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
