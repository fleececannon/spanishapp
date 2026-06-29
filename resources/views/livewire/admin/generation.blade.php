<div class="space-y-6">
    <div>
        <flux:heading size="xl">Generate cards</flux:heading>
        <flux:text class="mt-1">
            Builds new AI sentences from the {{ $unlockedVerbs }} unlocked verbs and unlocked words. Cards publish straight to the deck.
        </flux:text>
    </div>

    <flux:card>
        <form wire:submit="generate" class="space-y-4">
            <div class="flex flex-wrap items-end gap-4">
                <flux:input type="number" wire:model="count" label="How many" min="1" max="20" class="w-32" />
                <flux:input wire:model="emphasis" label="Emphasis (optional)" placeholder="e.g. lean on the past tense" class="flex-1 min-w-64" />
                <flux:button type="submit" variant="primary" icon="sparkles">
                    <span wire:loading.remove wire:target="generate">Generate</span>
                    <span wire:loading wire:target="generate">Generating…</span>
                </flux:button>
            </div>
        </form>
        <div wire:loading wire:target="generate" class="mt-3">
            <flux:text class="text-zinc-500">Asking Claude for fresh sentences — this can take a few seconds.</flux:text>
        </div>
    </flux:card>

    <div>
        <flux:heading size="lg" class="mb-3">Recent cards ({{ $activeCount }} active)</flux:heading>
        <div class="space-y-2">
            @forelse ($recent as $card)
                <flux:card wire:key="card-{{ $card->id }}" class="py-3">
                    <div class="font-medium">{{ $card->spanish }}</div>
                    <div class="text-zinc-500 text-sm">{{ $card->english }}</div>
                    <div class="mt-1 text-xs text-zinc-400">
                        @php $mm = $card->must_match ?? []; @endphp
                        tense: {{ $mm['tense'] ?? '—' }} · subject: {{ $mm['subject'] ?? '—' }} · gender: {{ $mm['gender'] ?? '—' }}
                    </div>
                </flux:card>
            @empty
                <flux:text class="text-zinc-500">No cards yet. Generate your first batch above.</flux:text>
            @endforelse
        </div>
    </div>
</div>
