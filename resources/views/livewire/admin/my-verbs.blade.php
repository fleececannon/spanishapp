<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">My Verbs</flux:heading>
            <flux:text class="mt-1">
                <span class="font-medium">{{ $known }}</span> known ·
                <span class="font-medium">{{ $training }}</span> in training ·
                <span class="font-medium">{{ $total - $known - $training }}</span> waiting ·
                <span class="font-medium">{{ $dueToday }}</span> due today
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button :href="route('admin.practice')" wire:navigate icon="play" variant="primary">Practice</flux:button>
            <flux:button wire:click="openBulk" icon="clipboard-document-list">Paste list</flux:button>
            <flux:button wire:click="openAdd" icon="plus">Add verb</flux:button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search Spanish or English…" icon="magnifying-glass" class="max-w-64" />
        <flux:select wire:model.live="filter" class="max-w-44">
            <flux:select.option value="all">All verbs</flux:select.option>
            <flux:select.option value="training">In training</flux:select.option>
            <flux:select.option value="known">Known</flux:select.option>
            <flux:select.option value="waiting">Waiting</flux:select.option>
        </flux:select>
        <flux:button.group>
            <flux:button wire:click="sortBy('score')" size="sm" :variant="$sort === 'score' ? 'filled' : 'outline'">By score</flux:button>
            <flux:button wire:click="sortBy('spanish')" size="sm" :variant="$sort === 'spanish' ? 'filled' : 'outline'">A–Z</flux:button>
        </flux:button.group>
    </div>

    <flux:card class="py-2">
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($verbs as $verb)
                <div class="flex items-center gap-3 py-2" wire:key="my-verb-{{ $verb->id }}">
                    <span class="w-9 shrink-0 text-right font-mono text-sm tabular-nums {{ $verb->frequency_score >= 70 ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'text-zinc-400' }}">
                        {{ $verb->frequency_score }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="font-medium {{ $verb->mastered ? 'text-zinc-400 line-through decoration-zinc-300 dark:decoration-zinc-600' : '' }}">{{ $verb->spanish }}</span>
                        <span class="text-sm text-zinc-500">— {{ $verb->english }}</span>
                    </div>
                    @if ($verb->reps > 0 && ! $verb->mastered)
                        <flux:badge size="sm" color="zinc" title="Current gap: {{ $verb->interval_days }} days">{{ $verb->interval_days }}d</flux:badge>
                    @endif
                    <label class="flex cursor-pointer items-center gap-1.5 whitespace-nowrap text-xs text-zinc-500">
                        <input type="checkbox" @checked($verb->unlocked)
                            wire:click="toggleUnlocked({{ $verb->id }})"
                            class="size-4 rounded border-zinc-300" />
                        training
                    </label>
                    <label class="flex cursor-pointer items-center gap-1.5 whitespace-nowrap text-xs text-zinc-500">
                        <input type="checkbox" @checked($verb->mastered)
                            wire:click="toggleMastered({{ $verb->id }})"
                            class="size-4 rounded border-zinc-300" />
                        known
                    </label>
                    <flux:button wire:click="delete({{ $verb->id }})" wire:confirm="Remove this verb?" size="xs" variant="ghost" icon="trash" />
                </div>
            @empty
                <flux:text class="py-4 text-zinc-500">Nothing matches.</flux:text>
            @endforelse
        </div>
    </flux:card>

    {{ $verbs->links() }}

    <flux:modal name="my-verb-form" class="md:w-96">
        <form wire:submit="addVerb" class="space-y-4">
            <flux:heading size="lg">Add a verb</flux:heading>
            <flux:input wire:model="newSpanish" label="Spanish (infinitive)" placeholder="charlar" />
            <flux:input wire:model="newEnglish" label="English" placeholder="to chat" />
            <flux:input wire:model="newScore" type="number" label="Frequency score (1–100)" min="1" max="100" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Add</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="my-verb-bulk" class="md:w-[36rem]">
        <form wire:submit="importBulk" class="space-y-4">
            <flux:heading size="lg">Paste a verb list</flux:heading>
            <flux:text class="text-sm">One per line: <code>spanish | english</code> or <code>spanish | english | score</code>. Tabs work too. Duplicates are skipped.</flux:text>
            <flux:textarea wire:model="bulk" rows="10" class="font-mono text-xs" placeholder="charlar | to chat | 55&#10;madrugar | to get up early | 40" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Import</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
