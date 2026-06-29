<div class="space-y-6">
    <div>
        <flux:heading size="xl">Generate cards</flux:heading>
        <flux:text class="mt-1">
            Builds new AI sentences from the {{ $unlockedVerbs }} unlocked verbs and unlocked words. Cards publish straight to the deck.
        </flux:text>
    </div>

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-end gap-4">
            <flux:input type="number" wire:model="count" label="How many" min="1" max="20" class="w-32" />
            <flux:input wire:model="emphasis" label="Emphasis (optional)" placeholder="e.g. lean on the past tense" class="flex-1 min-w-64" />
            <flux:button wire:click="generate" variant="primary" icon="sparkles">
                <span wire:loading.remove wire:target="generate,refresh,rebuild">Generate more</span>
                <span wire:loading wire:target="generate,refresh,rebuild">Working…</span>
            </flux:button>
        </div>

        <flux:separator />

        <div class="flex flex-wrap items-center gap-3">
            <flux:button wire:click="refresh" variant="subtle" icon="arrow-path">Refresh weak cards</flux:button>
            <flux:text class="text-zinc-500 text-sm">Retires cards the kids keep missing and backfills the same number. Progress on the rest is kept.</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button wire:click="rebuild" wire:confirm="This deletes EVERY card and resets ALL of the kids' progress. Continue?" variant="danger" icon="trash">Rebuild deck</flux:button>
            <flux:text class="text-zinc-500 text-sm">Nuclear reset — wipes all cards and schedules, then builds a fresh batch of the size above.</flux:text>
        </div>

        <div wire:loading wire:target="generate,refresh,rebuild">
            <flux:text class="text-zinc-500">Asking Claude — this can take a few seconds.</flux:text>
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
