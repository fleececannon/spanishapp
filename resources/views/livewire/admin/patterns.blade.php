<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Patterns</flux:heading>
            <flux:text class="mt-1">Sentence-building rules the AI must follow. Toggle one on to release it; off to withhold it.</flux:text>
        </div>
        <flux:button wire:click="openAdd" variant="primary" icon="plus">Add pattern</flux:button>
    </div>

    <div class="space-y-2">
        @forelse ($patterns as $pattern)
            <flux:card wire:key="pattern-{{ $pattern->id }}" class="py-3">
                <div class="flex items-start gap-3">
                    <flux:switch wire:click="toggle({{ $pattern->id }})" :checked="$pattern->enabled" class="mt-1" />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium">{{ $pattern->name }}</div>
                        <div class="text-zinc-500 text-sm">{{ $pattern->instruction }}</div>
                    </div>
                    <flux:button wire:click="openEdit({{ $pattern->id }})" size="xs" variant="ghost" icon="pencil" />
                    <flux:button wire:click="delete({{ $pattern->id }})" wire:confirm="Remove this pattern?" size="xs" variant="ghost" icon="trash" />
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No patterns yet. Add one to start shaping how sentences are built.</flux:text>
        @endforelse
    </div>

    <flux:modal name="pattern-form" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit pattern' : 'Add a pattern' }}</flux:heading>
            <flux:input wire:model="name" label="Name" placeholder="Direct-object pronoun placement" />
            <flux:textarea wire:model="instruction" label="Instruction (sent to the AI)" rows="4"
                placeholder="Use direct-object pronouns (lo, la, le...). Prefer them before the verb: 'lo quiero.'" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save' : 'Add' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
