<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Coverage</flux:heading>
            <flux:text class="mt-1">Every unlocked verb-tense (key verbs across all persons) and target word should appear in at least one card.</flux:text>
        </div>
        <flux:button wire:click="fillGaps" variant="primary" icon="sparkles">
            <span wire:loading.remove wire:target="fillGaps">Fill gaps</span>
            <span wire:loading wire:target="fillGaps">Filling…</span>
        </flux:button>
    </div>

    <flux:card>
        <div class="flex items-center justify-between mb-2">
            <flux:heading size="lg">{{ $summary['percent'] }}% covered</flux:heading>
            <flux:text class="text-zinc-500">{{ $summary['covered_slots'] }} / {{ $summary['total_slots'] }} slots · {{ $summary['gap_count'] }} missing</flux:text>
        </div>
        <div class="h-3 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
            <div class="h-full rounded-full bg-green-500" style="width: {{ $summary['percent'] }}%"></div>
        </div>
        <div wire:loading wire:target="fillGaps" class="mt-3">
            <flux:text class="text-zinc-500">Asking Claude to cover the missing pieces — this runs several rounds and can take a bit.</flux:text>
        </div>
    </flux:card>

    @foreach ($summary['groups'] as $tense => $g)
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="capitalize">{{ $tense }}</flux:heading>
                <flux:badge :color="$g['covered'] === $g['total'] ? 'green' : 'amber'">
                    {{ $g['covered'] }} / {{ $g['total'] }}
                </flux:badge>
            </div>
            @if (! empty($g['missing']))
                <flux:text class="mt-2 text-sm text-zinc-500">
                    Missing: {{ implode(', ', array_slice($g['missing'], 0, 30)) }}{{ count($g['missing']) > 30 ? '…' : '' }}
                </flux:text>
            @endif
        </flux:card>
    @endforeach

    <flux:card>
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Target words</flux:heading>
            <flux:badge :color="$summary['words']['covered'] === $summary['words']['total'] ? 'green' : 'amber'">
                {{ $summary['words']['covered'] }} / {{ $summary['words']['total'] }}
            </flux:badge>
        </div>
        @if (! empty($summary['words']['missing']))
            <flux:text class="mt-2 text-sm text-zinc-500">
                Missing: {{ implode(', ', array_slice($summary['words']['missing'], 0, 40)) }}{{ count($summary['words']['missing']) > 40 ? '…' : '' }}
            </flux:text>
        @endif
    </flux:card>
</div>
