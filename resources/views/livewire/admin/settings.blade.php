<div class="space-y-6">
    <flux:heading size="xl">Settings</flux:heading>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-3">
            <flux:heading size="lg">House style</flux:heading>
            <flux:text class="text-zinc-500">The voice and forgiveness rules sent to the AI for both generating and grading. This is the lever for how strict grading feels.</flux:text>
            <flux:textarea wire:model="houseStyle" rows="8" />
            <flux:error name="houseStyle" />
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Daily new-card pace</flux:heading>
            <flux:text class="text-zinc-500">How many brand-new cards each kid is introduced per session.</flux:text>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ($kids as $kid)
                    <flux:input type="number" min="0" max="50" wire:model="paces.{{ $kid->id }}" :label="$kid->name" wire:key="pace-{{ $kid->id }}" />
                @endforeach
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Spaced-repetition tuning</flux:heading>
            <div class="grid sm:grid-cols-2 gap-4">
                <flux:input type="number" step="1" wire:model="startingInterval" label="Starting interval (days)" />
                <flux:input type="number" step="0.1" wire:model="startingEase" label="Starting ease" />
                <flux:input type="number" step="0.05" wire:model="missPenalty" label="Miss penalty (ease drop)" />
                <flux:input type="number" step="0.05" wire:model="minEase" label="Minimum ease" />
            </div>
        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save settings</flux:button>
        </div>
    </form>
</div>
