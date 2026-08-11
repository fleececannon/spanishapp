<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Coverage</flux:heading>
            <flux:text class="mt-1">Every unlocked verb-tense (key verbs across all persons) and target word should appear in at least one card.</flux:text>
        </div>
        @if ($running)
            <flux:button wire:click="stop" variant="danger" icon="stop">Stop</flux:button>
        @else
            <flux:button wire:click="start" variant="primary" icon="sparkles" :disabled="$summary['open_gap_count'] === 0">
                @if ($summary['gap_count'] === 0)
                    Fully covered
                @elseif ($summary['open_gap_count'] === 0)
                    Drafts awaiting review
                @else
                    Fill gaps
                @endif
            </flux:button>
        @endif
    </div>

    {{-- While running, each poll fires one short round (a single Claude call). --}}
    @if ($running)
        <div wire:poll.750ms="step"></div>
    @endif

    <flux:card>
        <div class="flex items-center justify-between mb-2">
            <flux:heading size="lg">{{ $summary['percent'] }}% covered</flux:heading>
            <flux:text class="text-zinc-500">{{ $summary['covered_slots'] }} / {{ $summary['total_slots'] }} slots · {{ $summary['gap_count'] }} missing</flux:text>
        </div>
        <div class="h-3 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden flex">
            <div class="h-full bg-green-500 transition-all duration-500" style="width: {{ $summary['percent'] }}%"></div>
            @if ($summary['total_slots'] > 0 && $summary['draft_slots'] > 0)
                <div class="h-full bg-amber-400 transition-all duration-500" style="width: {{ round($summary['draft_slots'] / $summary['total_slots'] * 100) }}%"></div>
            @endif
        </div>
        @if ($summary['draft_slots'] > 0)
            <flux:text class="mt-3 text-amber-600 dark:text-amber-400">
                {{ $summary['draft_slots'] }} slot(s) have draft cards awaiting your review —
                <flux:link :href="route('admin.cards', ['filter' => 'draft'])" wire:navigate>approve them on the Cards page</flux:link>
                to count them as covered.
            </flux:text>
        @endif
        @if ($running)
            <flux:text class="mt-3 text-zinc-500">
                Filling… added {{ $createdThisRun }} draft card(s) so far. This runs one short round at a time — leave the page open; it stops on its own once every gap has a draft.
            </flux:text>
        @endif
    </flux:card>

    @foreach ($summary['groups'] as $tense => $g)
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg" class="capitalize">{{ $tense }}</flux:heading>
                <div class="flex items-center gap-1">
                    @if ($g['drafted'] > 0)
                        <flux:badge color="amber">{{ $g['drafted'] }} in review</flux:badge>
                    @endif
                    <flux:badge :color="$g['covered'] === $g['total'] ? 'green' : 'zinc'">
                        {{ $g['covered'] }} / {{ $g['total'] }}
                    </flux:badge>
                </div>
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
            <div class="flex items-center gap-1">
                @if ($summary['words']['drafted'] > 0)
                    <flux:badge color="amber">{{ $summary['words']['drafted'] }} in review</flux:badge>
                @endif
                <flux:badge :color="$summary['words']['covered'] === $summary['words']['total'] ? 'green' : 'zinc'">
                    {{ $summary['words']['covered'] }} / {{ $summary['words']['total'] }}
                </flux:badge>
            </div>
        </div>
        @if (! empty($summary['words']['missing']))
            <flux:text class="mt-2 text-sm text-zinc-500">
                Missing: {{ implode(', ', array_slice($summary['words']['missing'], 0, 40)) }}{{ count($summary['words']['missing']) > 40 ? '…' : '' }}
            </flux:text>
        @endif
    </flux:card>
</div>
