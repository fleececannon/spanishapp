<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text class="mt-1">How each kid is doing, at a glance.</flux:text>
        </div>
        @if ($kids->count() > 1)
            <flux:radio.group wire:model.live="kid" variant="segmented">
                @foreach ($kids as $k)
                    <flux:radio :value="$k->id" :label="$k->name" />
                @endforeach
            </flux:radio.group>
        @endif
    </div>

    @if (! $selected)
        <flux:card>
            <flux:text class="text-zinc-500">No kids yet — add one and their progress will show up here.</flux:text>
        </flux:card>
    @else
        {{-- Stat tiles --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold {{ $dueToday > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ $dueToday }}</div>
                <div class="mt-1 text-xs text-zinc-500">due today</div>
            </flux:card>
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold text-sky-600 dark:text-sky-400">{{ $newCount }}</div>
                <div class="mt-1 text-xs text-zinc-500">never seen</div>
            </flux:card>
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold">{{ $seen }}</div>
                <div class="mt-1 text-xs text-zinc-500">cards seen</div>
            </flux:card>
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold text-green-600 dark:text-green-400">{{ $masteredCards }}</div>
                <div class="mt-1 text-xs text-zinc-500">mastered</div>
            </flux:card>
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold {{ $needsWork > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ $needsWork }}</div>
                <div class="mt-1 text-xs text-zinc-500">needs work</div>
            </flux:card>
            <flux:card class="py-4 text-center">
                <div class="text-3xl font-semibold">{{ $accuracy === null ? '—' : $accuracy.'%' }}</div>
                <div class="mt-1 text-xs text-zinc-500">accuracy</div>
            </flux:card>
        </div>

        {{-- Deck progress --}}
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Deck progress</flux:heading>
                <flux:text class="text-zinc-500">
                    {{ $masteredCards }} mastered · {{ $seen }} seen · {{ $totalCards }} cards
                    @if ($reviewedToday > 0) · {{ $reviewedToday }} reviewed today @endif
                </flux:text>
            </div>
            <div class="flex h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700" role="img"
                aria-label="{{ $masteredCards }} of {{ $totalCards }} cards mastered, {{ $seen }} seen">
                @if ($totalCards > 0)
                    <div class="h-full bg-green-500" style="width: {{ round($masteredCards / $totalCards * 100) }}%"></div>
                    <div class="h-full bg-sky-500/70" style="width: {{ round(max(0, $seen - $masteredCards) / $totalCards * 100) }}%"></div>
                @endif
            </div>
            <div class="flex gap-4 text-xs text-zinc-500">
                <span class="flex items-center gap-1.5"><span class="inline-block size-2 rounded-full bg-green-500"></span> mastered ({{ $masteryPct }}%)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-2 rounded-full bg-sky-500/70"></span> still learning</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span> not started</span>
            </div>
        </flux:card>

        {{-- Review schedule, next 14 days --}}
        <flux:card class="space-y-3">
            <flux:heading size="lg">Review schedule · next 14 days</flux:heading>
            <div class="flex h-28 items-end gap-[2px]" role="img" aria-label="Reviews due per day over the next 14 days">
                @foreach ($upcoming as $day)
                    <div class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-1"
                        title="{{ $day['dow'] }} {{ $day['day'] }}: {{ $day['count'] }} review{{ $day['count'] === 1 ? '' : 's' }}{{ $loop->first ? ' (incl. overdue)' : '' }}">
                        @if ($day['count'] > 0)
                            <span class="text-[10px] font-semibold leading-none text-zinc-600 dark:text-zinc-300">{{ $day['count'] }}</span>
                            <div class="w-full max-w-4 rounded-t-[4px] {{ $loop->first ? 'bg-amber-500' : 'bg-indigo-500' }}"
                                style="height: {{ max(6, round($day['count'] / $upcomingMax * 76)) }}px"></div>
                        @else
                            <div class="h-[2px] w-full max-w-4 rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex gap-[2px] border-t border-zinc-200 pt-1.5 dark:border-zinc-700">
                @foreach ($upcoming as $day)
                    <div class="min-w-0 flex-1 text-center">
                        <div class="text-[10px] leading-tight text-zinc-400">{{ $loop->first ? 'Today' : substr($day['dow'], 0, 1) }}</div>
                        <div class="text-[10px] leading-tight text-zinc-400">{{ $loop->first ? '' : $day['day'] }}</div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Concept mastery --}}
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach (['verbs' => 'Verbs', 'words' => 'Words'] as $group => $label)
                <flux:card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">{{ $label }}</flux:heading>
                        <flux:text class="text-zinc-500">
                            {{ count($concepts[$group]['mastered']) }} mastered ·
                            {{ count($concepts[$group]['learning']) }} learning ·
                            {{ count($concepts[$group]['new']) }} not started
                        </flux:text>
                    </div>

                    @if (! empty($concepts[$group]['mastered']))
                        <div>
                            <flux:heading size="sm" class="mb-2 text-green-600 dark:text-green-400">Mastered</flux:heading>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($concepts[$group]['mastered'] as $name)
                                    <flux:badge size="sm" color="green">{{ $name }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($concepts[$group]['learning']))
                        <div>
                            <flux:heading size="sm" class="mb-2 text-sky-600 dark:text-sky-400">Still learning</flux:heading>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($concepts[$group]['learning'] as $name)
                                    <flux:badge size="sm" color="sky">{{ $name }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($concepts[$group]['new']))
                        <details>
                            <summary class="cursor-pointer text-sm text-zinc-500">
                                Not started yet ({{ count($concepts[$group]['new']) }})
                            </summary>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($concepts[$group]['new'] as $name)
                                    <flux:badge size="sm" color="zinc">{{ $name }}</flux:badge>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    @if (empty($concepts[$group]['mastered']) && empty($concepts[$group]['learning']) && empty($concepts[$group]['new']))
                        <flux:text class="text-zinc-500">Nothing unlocked yet.</flux:text>
                    @endif
                </flux:card>
            @endforeach
        </div>

        {{-- Trouble spots --}}
        <flux:card class="space-y-3">
            <flux:heading size="lg">Trickiest cards</flux:heading>
            @forelse ($trouble as $row)
                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 last:border-0 last:pb-0 dark:border-zinc-800">
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ $row['spanish'] }}</div>
                        <div class="truncate text-sm text-zinc-500">{{ $row['english'] }}</div>
                    </div>
                    <flux:badge color="amber" size="sm" class="shrink-0">{{ $row['lapses'] }} miss{{ $row['lapses'] === 1 ? '' : 'es' }} / {{ $row['reps'] }} tries</flux:badge>
                </div>
            @empty
                <flux:text class="text-zinc-500">No stumbles yet — nothing has been missed.</flux:text>
            @endforelse
        </flux:card>
    @endif
</div>
