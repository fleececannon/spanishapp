<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-md shadow-purple-600/25">
                <x-app-logo-icon class="size-7 text-white" />
            </div>
            <div>
                <flux:heading size="lg" class="leading-tight">¡Hola, {{ $name }}!</flux:heading>
                <flux:text class="text-sm text-zinc-500">Amani Spanish</flux:text>
            </div>
        </div>
        <flux:button wire:click="logout" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
    </div>

    {{-- Today's work --}}
    <flux:card class="space-y-6 rounded-3xl border-none shadow-xl">
        @if ($todo > 0)
            <div class="text-center">
                <div class="text-6xl font-extrabold text-zinc-900 dark:text-white">{{ $todo }}</div>
                <flux:text class="mt-1 text-lg">card{{ $todo === 1 ? '' : 's' }} ready to practice</flux:text>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-sky-50 p-4 text-center dark:bg-sky-950/40">
                    <div class="text-2xl font-bold text-sky-600 dark:text-sky-300">{{ $newCount }}</div>
                    <div class="text-sm text-sky-700/80 dark:text-sky-300/80">New</div>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4 text-center dark:bg-emerald-950/40">
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ $reviewsDue }}</div>
                    <div class="text-sm text-emerald-700/80 dark:text-emerald-300/80">Reviews due</div>
                </div>
            </div>

            <flux:button wire:click="startLearning" variant="primary" class="w-full !h-14 !rounded-2xl !text-lg !font-bold">
                Start learning 🚀
            </flux:button>
        @else
            <div class="py-6 text-center space-y-2">
                <div class="text-5xl">🎉</div>
                <flux:heading size="xl">All caught up!</flux:heading>
                <flux:text>Nothing due right now. Come back later for more.</flux:text>
            </div>
        @endif
    </flux:card>

    {{-- Progress --}}
    <flux:card class="mt-4 space-y-4 rounded-3xl border-none shadow-sm">
        <div class="flex items-center justify-between">
            <flux:heading size="sm" class="text-zinc-500">Your progress</flux:heading>
            <flux:text class="text-sm font-semibold">{{ $masteryPct }}% mastered</flux:text>
        </div>
        <div class="h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-500" style="width: {{ $masteryPct }}%"></div>
        </div>
        <div class="grid grid-cols-3 gap-3 pt-1 text-center">
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $mastered }}</div>
                <div class="text-xs text-zinc-500">Mastered</div>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $seen }}</div>
                <div class="text-xs text-zinc-500">Seen</div>
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $total }}</div>
                <div class="text-xs text-zinc-500">Total cards</div>
            </div>
        </div>
    </flux:card>
</div>
