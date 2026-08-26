<?php

namespace App\Livewire\Admin;

use App\Enums\ReviewResult;
use App\Models\MyVerb;
use App\Services\Srs\MyVerbScheduler;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My Practice')]
class MyPractice extends Component
{
    /** Remaining verb ids this session (FIFO). */
    public array $queue = [];

    public ?int $currentId = null;

    public bool $revealed = false;

    public bool $done = false;

    public function mount(): void
    {
        // Due reviews first (shuffled), then never-seen verbs, most common first.
        $due = MyVerb::inTraining()
            ->whereDate('due', '<=', Carbon::today())
            ->get()
            ->shuffle();

        $fresh = MyVerb::inTraining()
            ->whereNull('due')
            ->orderByDesc('frequency_score')
            ->orderBy('spanish')
            ->get();

        $this->queue = $due->concat($fresh)->pluck('id')->unique()->values()->all();
        $this->advance();
    }

    public function reveal(): void
    {
        $this->revealed = true;
    }

    public function mark(MyVerbScheduler $scheduler, bool $correct): void
    {
        if ($this->currentId === null) {
            return;
        }

        $verb = MyVerb::find($this->currentId);
        if (! $verb) {
            $this->advance();

            return;
        }

        $scheduler->apply($verb, $correct ? ReviewResult::Pass : ReviewResult::NeedsWork);

        // A miss comes back later this session.
        if (! $correct) {
            $this->queue[] = $verb->id;
        }

        $this->advance();
    }

    /** Move past a verb without judging it — no schedule change, no rep. */
    public function skip(): void
    {
        if ($this->currentId === null) {
            return;
        }

        $this->advance();
    }

    private function advance(): void
    {
        $this->revealed = false;

        if (empty($this->queue)) {
            $this->currentId = null;
            $this->done = true;

            return;
        }

        $this->currentId = array_shift($this->queue);
    }

    public function render()
    {
        return view('livewire.admin.my-practice', [
            'verb' => $this->currentId ? MyVerb::find($this->currentId) : null,
            'remaining' => count($this->queue) + ($this->currentId ? 1 : 0),
        ]);
    }
}
