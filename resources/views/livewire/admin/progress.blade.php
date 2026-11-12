<div class="space-y-6">
    <div>
        <flux:heading size="xl">Progress</flux:heading>
        <flux:text class="mt-1">A quick read on each kid — enough to tell whether they're ready for the next layer.</flux:text>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($stats as $row)
            <flux:card wire:key="kid-{{ $row['kid']->id }}" class="space-y-5">
                <flux:heading size="lg">{{ $row['kid']->name }}</flux:heading>

                @foreach ($row['decks'] as $deck)
                    <div wire:key="kid-{{ $row['kid']->id }}-{{ $deck['direction']->value }}" class="space-y-3 {{ $loop->first ? '' : 'border-t border-zinc-200 pt-5 dark:border-zinc-700' }}">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm" class="text-zinc-500">{{ $deck['direction']->label() }}</flux:heading>
                            <flux:badge :color="$deck['due_now'] > 0 ? 'amber' : 'green'">
                                {{ $deck['due_now'] }} due now
                            </flux:badge>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500">Mastery</span>
                                <span class="font-medium">{{ $deck['mastery'] }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-green-500" style="width: {{ $deck['mastery'] }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-2xl font-semibold">{{ $deck['seen'] }}</div>
                                <div class="text-xs text-zinc-500">cards seen</div>
                            </div>
                            <div>
                                <div class="text-2xl font-semibold text-green-600">{{ $deck['learned'] }}</div>
                                <div class="text-xs text-zinc-500">learned</div>
                            </div>
                            <div>
                                <div class="text-2xl font-semibold text-amber-600">{{ $deck['needs_work'] }}</div>
                                <div class="text-xs text-zinc-500">needs work</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </flux:card>
        @endforeach
    </div>
</div>
