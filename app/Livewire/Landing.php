<?php

namespace App\Livewire;

use App\Models\Kid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.kid')]
#[Title('Amani Spanish App')]
class Landing extends Component
{
    #[Validate('required|string')]
    public string $name = '';

    #[Validate('required|string')]
    public string $password = '';

    public function mount(): void
    {
        // Already signed in? Send them where they belong.
        if (Auth::guard('web')->check()) {
            $this->redirectRoute('dashboard', navigate: true);
        } elseif (Auth::guard('kid')->check()) {
            $this->redirectRoute('kid.home', navigate: true);
        }
    }

    /** Kids sign in by name + password. */
    public function login(): void
    {
        $this->validate();

        if (! Auth::guard('kid')->attempt(['name' => $this->name, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'password' => "That name and password don't match.",
            ]);
        }

        session()->regenerate();
        $this->redirectRoute('kid.home', navigate: true);
    }

    public function render()
    {
        return view('livewire.landing', [
            'kids' => Kid::orderBy('name')->pluck('name'),
        ]);
    }
}
