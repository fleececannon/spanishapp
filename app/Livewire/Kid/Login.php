<?php

namespace App\Livewire\Kid;

use App\Models\Kid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.kid')]
#[Title('Sign in')]
class Login extends Component
{
    #[Validate('required|string')]
    public string $name = '';

    #[Validate('required|string')]
    public string $password = '';

    public function mount(): void
    {
        if (Auth::guard('kid')->check()) {
            $this->redirectRoute('kid.home', navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate();

        // remember: true keeps the kids signed in across sessions — no more
        // daily logins on the family devices.
        if (! Auth::guard('kid')->attempt(['name' => $this->name, 'password' => $this->password], remember: true)) {
            throw ValidationException::withMessages([
                'password' => 'That name and password don\'t match.',
            ]);
        }

        session()->regenerate();
        $this->redirectRoute('kid.home', navigate: true);
    }

    public function render()
    {
        return view('livewire.kid.login', [
            'kids' => Kid::orderBy('name')->pluck('name'),
        ]);
    }
}
