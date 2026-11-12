<?php

namespace App\Livewire\Kid;

use App\Enums\Direction;
use App\Models\Card;
use App\Models\ReviewState;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kid')]
#[Title('Home')]
class Dashboard extends Component
{
    /** Interval (days) at which a card counts as "mastered". */
    private const MASTERED_INTERVAL = 7;

    /** Selected deck tab (a Direction value). Each deck is tracked and practised on its own. */
    #[Url]
    public string $tab = Direction::EsToEn->value;

    public function startLearning()
    {
        return $this->redirectRoute('kid.review', ['direction' => $this->deck()->value], navigate: true);
    }

    private function deck(): Direction
    {
        return Direction::fromInput($this->tab);
    }

    public function logout()
    {
        auth('kid')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirectRoute('kid.login', navigate: true);
    }

    public function render()
    {
        $kid = auth('kid')->user();
        $deck = $this->deck();
        $this->tab = $deck->value;

        // How much is waiting in each deck, so the tabs can say so.
        $tabs = collect(Direction::cases())->map(fn (Direction $d) => [
            'value' => $d->value,
            'label' => $d->label(),
            'todo' => $this->todoFor($kid->id, $d),
        ]);

        return view('livewire.kid.dashboard', [
            'name' => $kid->name,
            'deck' => $deck,
            'tabs' => $tabs,
            ...$this->statsFor($kid->id, $deck),
        ]);
    }

    /** Cards waiting right now in one deck: reviews that have come due plus never-seen cards. */
    private function todoFor(int $kidId, Direction $deck): int
    {
        $states = ReviewState::where('kid_id', $kidId)->onActiveCards($deck);

        $reviewsDue = (clone $states)->whereDate('due', '<=', Carbon::today()->toDateString())->count();
        $newCount = max(0, Card::active()->direction($deck)->count() - (clone $states)->count());

        return $reviewsDue + $newCount;
    }

    /** @return array<string, mixed> Everything the selected deck's panel shows. */
    private function statsFor(int $kidId, Direction $deck): array
    {
        $today = Carbon::today()->toDateString();

        // onActiveCards: history for retired cards is kept but must never be
        // counted here, or archived cards keep showing up as due and "seen"
        // can outgrow the deck (which drove the new-card count negative).
        $states = ReviewState::where('kid_id', $kidId)->onActiveCards($deck);

        $reviewsDue = (clone $states)->whereDate('due', '<=', $today)->count();
        $seen = (clone $states)->count();
        $mastered = (clone $states)->where('interval_days', '>=', self::MASTERED_INTERVAL)->count();

        $totalActive = Card::active()->direction($deck)->count();
        $newCount = max(0, $totalActive - $seen);

        // Reviews landing in the next 14 days (tomorrow onward), one bucket per day.
        $dueByDay = (clone $states)
            ->whereBetween('due', [Carbon::tomorrow(), Carbon::today()->addDays(14)])
            ->get()
            ->countBy(fn (ReviewState $s) => $s->due->toDateString());

        $upcoming = collect(range(1, 14))->map(function (int $offset) use ($dueByDay) {
            $date = Carbon::today()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'dow' => $date->format('D'),
                'day' => $date->format('j'),
                'count' => (int) ($dueByDay[$date->toDateString()] ?? 0),
            ];
        });

        return [
            'upcoming' => $upcoming,
            'upcomingMax' => max(1, $upcoming->max('count')),
            'todo' => $reviewsDue + $newCount,
            'reviewsDue' => $reviewsDue,
            'newCount' => $newCount,
            'seen' => $seen,
            'mastered' => $mastered,
            'total' => $totalActive,
            'masteryPct' => $totalActive > 0 ? (int) round($mastered / $totalActive * 100) : 0,
        ];
    }
}
