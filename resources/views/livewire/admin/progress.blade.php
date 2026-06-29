<div class="space-y-6">
    <div>
        <flux:heading size="xl">Progress</flux:heading>
        <flux:text class="mt-1">A quick read on each kid — enough to tell whether they're ready for the next layer.</flux:text>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($stats as $row)
            <flux:card wire:key="kid-{{ $row['kid']->id }}" class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ $row['kid']->name }}</flux:heading>
                    <flux:badge :color="$row['due_now'] > 0 ? 'amber' : 'green'">
                        {{ $row['due_now'] }} due now
                    </flux:badge>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-zinc-500">Mastery</span>
                        <span class="font-medium">{{ $row['mastery'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full bg-green-500" style="width: {{ $row['mastery'] }}%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-2xl font-semibold">{{ $row['seen'] }}</div>
                        <div class="text-xs text-zinc-500">cards seen</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-green-600">{{ $row['learned'] }}</div>
                        <div class="text-xs text-zinc-500">learned</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-amber-600">{{ $row['needs_work'] }}</div>
                        <div class="text-xs text-zinc-500">needs work</div>
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
