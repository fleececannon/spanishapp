<div>
    <flux:card class="space-y-6">
        <div class="text-center">
            <flux:heading size="xl">¡Hola! 👋</flux:heading>
            <flux:text class="mt-1">Pick your name to start.</flux:text>
        </div>

        <form wire:submit="login" class="space-y-4">
            <flux:select wire:model="name" label="Who are you?" placeholder="Choose your name">
                @foreach ($kids as $kidName)
                    <flux:select.option value="{{ $kidName }}">{{ $kidName }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="password" type="password" label="Password" />

            <flux:button type="submit" variant="primary" class="w-full">Start</flux:button>
        </form>
    </flux:card>
</div>
