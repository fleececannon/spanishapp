<?php

namespace Tests\Feature;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\Verb;
use App\Models\Word;
use App\Services\Coverage\CoverageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverageTest extends TestCase
{
    use RefreshDatabase;

    private function keyVerb(): Verb
    {
        return Verb::create([
            'spanish' => 'Tener', 'english' => 'to have', 'tag' => 'Key Verbs',
            'verb_class' => 'ER', 'enabled_tenses' => ['present'],
            'drill_all_forms' => true, 'unlocked' => true,
        ]);
    }

    private function setVerb(): Verb
    {
        return Verb::create([
            'spanish' => 'Caminar', 'english' => 'to walk', 'tag' => 'Verb Set 1',
            'verb_class' => 'AR', 'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false, 'unlocked' => true,
        ]);
    }

    private function cardUsing(array $uses, CardStatus $status = CardStatus::Active): Card
    {
        return Card::create([
            'source' => CardSource::Ai, 'spanish' => 's', 'english' => 'e',
            'test_direction' => 'es_to_en', 'uses_concepts' => $uses,
            'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
            'status' => $status,
        ]);
    }

    public function test_key_verb_requires_all_five_persons_per_conjugated_tense(): void
    {
        $this->keyVerb(); // present, drill -> 5 person slots

        $slots = app(CoverageService::class)->requiredSlots();

        $this->assertCount(5, $slots);
    }

    public function test_non_key_verb_requires_one_slot_per_tense(): void
    {
        $this->setVerb(); // infinitive, not drill -> 1 slot

        $this->assertCount(1, app(CoverageService::class)->requiredSlots());
    }

    public function test_target_words_are_required_but_ingredients_are_not(): void
    {
        Word::create(['spanish' => 'porque', 'english' => 'because', 'category' => 'connector', 'role' => 'target', 'unlocked' => true]);
        Word::create(['spanish' => 'agua', 'english' => 'water', 'category' => 'noun', 'role' => 'ingredient', 'unlocked' => true]);

        $this->assertCount(1, app(CoverageService::class)->requiredSlots());
    }

    public function test_coverage_credits_person_specific_use_for_key_verbs(): void
    {
        $verb = $this->keyVerb();
        $this->cardUsing([['type' => 'verb', 'id' => $verb->id, 'tense' => 'present', 'person' => '1st_singular']]);

        $summary = app(CoverageService::class)->summary();

        $this->assertSame(1, $summary['covered_slots']);
        $this->assertSame(4, $summary['gap_count']); // 5 persons, 1 covered
    }

    public function test_full_coverage_reports_one_hundred_percent(): void
    {
        $verb = $this->setVerb(); // 1 infinitive slot
        $this->cardUsing([['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]]);

        $summary = app(CoverageService::class)->summary();

        $this->assertSame(100, $summary['percent']);
        $this->assertSame(0, $summary['gap_count']);
    }

    public function test_legacy_card_without_tense_does_not_credit_coverage(): void
    {
        $verb = $this->setVerb();
        $this->cardUsing([['type' => 'verb', 'id' => $verb->id]]); // old shape, no tense

        $this->assertSame(0, app(CoverageService::class)->summary()['covered_slots']);
    }

    public function test_draft_cards_do_not_count_as_covered_but_block_regeneration(): void
    {
        $verb = $this->setVerb(); // 1 infinitive slot
        $this->cardUsing(
            [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
            CardStatus::Draft,
        );

        $summary = app(CoverageService::class)->summary();

        // Not covered until approved…
        $this->assertSame(0, $summary['covered_slots']);
        $this->assertSame(1, $summary['gap_count']);
        // …but the slot is spoken for, so generation must not refill it.
        $this->assertSame(1, $summary['draft_slots']);
        $this->assertSame(0, $summary['open_gap_count']);
        $this->assertSame([], app(CoverageService::class)->openGaps());
        $this->assertEmpty(app(CoverageService::class)->gapRequirements(12)['verbUses']);
    }

    public function test_approving_a_draft_makes_the_slot_covered(): void
    {
        $verb = $this->setVerb();
        $card = $this->cardUsing(
            [['type' => 'verb', 'id' => $verb->id, 'tense' => 'infinitive', 'person' => null]],
            CardStatus::Draft,
        );

        $card->update(['status' => CardStatus::Active]);

        $summary = app(CoverageService::class)->summary();
        $this->assertSame(100, $summary['percent']);
        $this->assertSame(0, $summary['draft_slots']);
    }

    public function test_gap_requirements_are_phrased_for_the_generator(): void
    {
        $this->keyVerb();
        $req = app(CoverageService::class)->gapRequirements(12);

        $this->assertNotEmpty($req['verbUses']);
        $this->assertStringContainsString('Tener', $req['verbUses'][0]);
        $this->assertStringContainsString('present', strtolower($req['verbUses'][0]));
    }
}
