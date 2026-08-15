<?php

namespace App\Livewire\Kid;

use App\Models\Card;
use App\Models\ReviewState;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.kid')]
#[Title('Home')]
class Dashboard extends Component
{
    /** Interval (days) at which a card counts as "mastered". */
    private const MASTERED_INTERVAL = 7;

    public function startLearning()
    {
        return $this->redirectRoute('kid.review', navigate: true);
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
        $today = Carbon::today()->toDateString();

        // onActiveCards: history for retired cards is kept but must never be
        // counted here, or archived cards keep showing up as due and "seen"
        // can outgrow the deck (which drove the new-card count negative).
        $states = ReviewState::where('kid_id', $kid->id)->onActiveCards();

        $reviewsDue = (clone $states)->whereDate('due', '<=', $today)->count();
        $seen = (clone $states)->count();
        $mastered = (clone $states)->where('interval_days', '>=', self::MASTERED_INTERVAL)->count();

        $totalActive = Card::active()->count();
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

        return view('livewire.kid.dashboard', [
            'upcoming' => $upcoming,
            'upcomingMax' => max(1, $upcoming->max('count')),
            'name' => $kid->name,
            'todo' => $reviewsDue + $newCount,
            'reviewsDue' => $reviewsDue,
            'newCount' => $newCount,
            'seen' => $seen,
            'mastered' => $mastered,
            'total' => $totalActive,
            'masteryPct' => $totalActive > 0 ? (int) round($mastered / $totalActive * 100) : 0,
        ]);
    }
}
