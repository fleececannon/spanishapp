<?php

namespace App\Livewire\Admin;

use App\Models\Kid;
use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Settings')]
class Settings extends Component
{
    #[Validate('required|string|max:4000')]
    public string $houseStyle = '';

    #[Validate('required|integer|min:1|max:30')]
    public int $startingInterval = 1;

    #[Validate('required|numeric|min:1.1|max:4')]
    public float $startingEase = 2.5;

    #[Validate('required|numeric|min:0|max:1')]
    public float $missPenalty = 0.2;

    #[Validate('required|numeric|min:1.1|max:2.5')]
    public float $minEase = 1.3;

    /** @var array<int,int> kidId => daily pace */
    public array $paces = [];

    public function mount(): void
    {
        $this->houseStyle = (string) Setting::get('house_style', '');

        $tuning = Setting::get('srs_tuning', []);
        $this->startingInterval = (int) ($tuning['starting_interval'] ?? 1);
        $this->startingEase = (float) ($tuning['starting_ease'] ?? 2.5);
        $this->missPenalty = (float) ($tuning['miss_penalty'] ?? 0.2);
        $this->minEase = (float) ($tuning['min_ease'] ?? 1.3);

        $this->paces = Kid::orderBy('name')->pluck('daily_new_card_pace', 'id')->all();
    }

    public function save(): void
    {
        $this->validate();

        Setting::put('house_style', $this->houseStyle);
        Setting::put('srs_tuning', [
            'starting_interval' => $this->startingInterval,
            'starting_ease' => $this->startingEase,
            'miss_penalty' => $this->missPenalty,
            'min_ease' => $this->minEase,
        ]);

        foreach ($this->paces as $kidId => $pace) {
            Kid::whereKey($kidId)->update(['daily_new_card_pace' => max(0, (int) $pace)]);
        }

        Flux::toast(variant: 'success', text: 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'kids' => Kid::orderBy('name')->get(),
        ]);
    }
}
