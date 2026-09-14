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

    public function test_a_sampled_tense_on_a_key_verb_needs_one_card_not_five(): void
    {
        $verb = $this->keyVerb(); // drill_all_forms, present
        $verb->update(['enabled_tenses' => ['present', 'imperfect'], 'sample_tenses' => ['imperfect']]);

        $slots = app(CoverageService::class)->requiredSlots();
        $present = array_filter($slots, fn ($s) => $s['kind'] === 'verb' && $s['tense'] === 'present');
        $imperfect = array_filter($slots, fn ($s) => $s['kind'] === 'verb' && $s['tense'] === 'imperfect');

        $this->assertCount(5, $present, 'un-sampled tense still drills every person');
        $this->assertCount(1, $imperfect, 'sampled tense wants exactly one card');
        $this->assertNull(array_values($imperfect)[0]['person']);
    }

    public function test_any_person_covers_a_sampled_tense_on_a_key_verb(): void
    {
        $verb = $this->keyVerb();
        $verb->update(['enabled_tenses' => ['imperfect'], 'sample_tenses' => ['imperfect']]);

        // The generator was asked for one form; whichever person it used, the slot is filled.
        $this->cardUsing([['type' => 'verb', 'id' => $verb->id, 'tense' => 'imperfect', 'person' => '3rd_plural']]);

        $summary = app(CoverageService::class)->summary();
        $this->assertSame(100, $summary['percent']);
        $this->assertSame(1, $summary['total_slots']);

        // ...and the requirement it was phrased from still named a person.
        $verb->update(['enabled_tenses' => ['imperfect', 'past'], 'sample_tenses' => ['imperfect', 'past']]);
        $req = app(CoverageService::class)->gapRequirements(12)['verbUses'];
        $this->assertCount(1, $req);
        $this->assertStringContainsString(' as ', $req[0]);
    }

    public function test_sample_verbs_are_asked_for_a_specific_person(): void
    {
        // A non-drill verb gets one card per tense. Without a named person the
        // model defaults to "yo" across the whole deck, so the requirement must
        // still spell a form out.
        $verb = $this->setVerb();
        $verb->update(['enabled_tenses' => ['present']]);

        $req = app(CoverageService::class)->gapRequirements(12);

        $this->assertCount(1, $req['verbUses'], 'sample verbs need exactly one slot per tense');
        $this->assertStringContainsString('Caminar', $req['verbUses'][0]);
        $this->assertStringContainsString(' as ', $req['verbUses'][0]);
    }

    public function test_infinitive_slots_never_name_a_person(): void
    {
        $verb = $this->setVerb(); // infinitive only

        $this->assertNull(app(CoverageService::class)->samplePerson($verb->id, 'infinitive'));
        $this->assertStringNotContainsString(' as ', app(CoverageService::class)->gapRequirements(12)['verbUses'][0]);
    }

    public function test_sample_person_is_stable_and_varies_by_tense(): void
    {
        $coverage = app(CoverageService::class);

        // Stable: regenerating a slot must ask for the same form, not reshuffle.
        $this->assertSame($coverage->samplePerson(7, 'present'), $coverage->samplePerson(7, 'present'));

        // Seeded on the tense too, so one verb's tenses don't all collapse onto
        // the same person. (Checked across a spread of ids — any single verb may
        // legitimately draw the same form twice.)
        $differing = collect(range(1, 40))
            ->filter(fn (int $id) => $coverage->samplePerson($id, 'present') !== $coverage->samplePerson($id, 'past'))
            ->count();

        $this->assertGreaterThan(20, $differing, 'present and past should mostly differ');
    }

    public function test_sample_persons_lean_on_the_forms_children_use(): void
    {
        $coverage = app(CoverageService::class);

        $spread = collect(range(1, 400))
            ->flatMap(fn (int $id) => [$coverage->samplePerson($id, 'present'), $coverage->samplePerson($id, 'past')])
            ->countBy();

        // All five forms show up...
        $this->assertCount(5, $spread);

        // ...but the singular persons carry the deck, and no single form runs away with it.
        $singulars = $spread['1st_singular'] + $spread['2nd_singular'] + $spread['3rd_singular'];
        $this->assertGreaterThan(600, $singulars, 'yo/tu/el should be ~80% of 800 picks');
        $this->assertLessThan(320, $spread->max(), 'no form should dominate');
    }
}
