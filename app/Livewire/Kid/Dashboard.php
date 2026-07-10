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

        $states = ReviewState::where('kid_id', $kid->id);

        $reviewsDue = (clone $states)->whereDate('due', '<=', $today)->count();
        $seen = (clone $states)->count();
        $mastered = (clone $states)->where('interval_days', '>=', self::MASTERED_INTERVAL)->count();

        $totalActive = Card::active()->count();
        $newCount = $totalActive - $seen;

        return view('livewire.kid.dashboard', [
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
