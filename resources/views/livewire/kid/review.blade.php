<div>
    <div class="mb-4 flex items-center justify-between">
        <flux:button :href="route('kid.home')" wire:navigate variant="ghost" size="sm" icon="home">Home</flux:button>
        <flux:button wire:click="logout" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
    </div>

    @if ($done)
        <flux:card class="text-center space-y-4 py-10">
            <div class="text-5xl">🎉</div>
            <flux:heading size="xl">All done for now!</flux:heading>
            <flux:text>You finished every card waiting for you. Come back later for more.</flux:text>
            <flux:button :href="route('kid.home')" wire:navigate variant="primary" class="!rounded-2xl">Back to home</flux:button>
        </flux:card>
    @elseif ($card)
        <flux:card class="space-y-5">
            <div class="flex items-center justify-between">
                <flux:text class="text-zinc-500 text-sm">{{ $remaining }} to go</flux:text>
                <flux:switch wire:model.live="outLoud" label="Out loud" align="left" />
            </div>

            <div class="rounded-xl bg-zinc-100 dark:bg-zinc-800 p-6 text-center">
                <span class="text-2xl font-semibold">{{ $card->spanish }}</span>
            </div>

            @if (! $showResult)
                @if ($outLoud)
                    {{-- Out-loud mode: say it together, reveal, then a grown-up marks it. --}}
                    @if (! $revealed)
                        <flux:button wire:click="reveal" variant="primary" class="w-full">Show answer</flux:button>
                    @else
                        <div class="text-center">
                            <flux:text class="text-zinc-500 text-sm">It means</flux:text>
                            <div class="font-medium text-lg">{{ $card->english }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:button wire:click="mark(false)" variant="danger" icon="x-mark">Missed it</flux:button>
                            <flux:button wire:click="mark(true)" variant="primary" icon="check">Got it</flux:button>
                        </div>
                    @endif
                @else
                    <flux:text class="text-zinc-500 text-sm">Type what it means in English.</flux:text>
                    <form wire:submit="submit" class="space-y-3">
                        <flux:textarea wire:model="answer" placeholder="In English…" rows="3" autofocus />
                        <flux:error name="answer" />
                        <flux:button type="submit" variant="primary" class="w-full">
                            <span wire:loading.remove wire:target="submit">Check</span>
                            <span wire:loading wire:target="submit">Checking…</span>
                        </flux:button>
                    </form>
                @endif

                {{-- Pass on this one — nothing is marked wrong. --}}
                <flux:button wire:click="skip" variant="ghost" size="sm" class="w-full" icon="arrow-right">
                    Skip for now
                </flux:button>
            @else
                <div class="space-y-4">
                    @if ($lastPassed)
                        <div class="rounded-xl bg-green-100 dark:bg-green-900/40 p-4 text-center">
                            <div class="text-3xl">✅</div>
                            <flux:heading class="mt-1 text-green-800 dark:text-green-200">¡Muy bien!</flux:heading>
                        </div>
                    @else
                        <div class="rounded-xl bg-amber-100 dark:bg-amber-900/40 p-4 text-center">
                            <div class="text-3xl">🔁</div>
                            <flux:heading class="mt-1 text-amber-800 dark:text-amber-200">Let's see this one again soon.</flux:heading>
                            @if ($nudge)
                                <flux:text class="mt-1 text-amber-800 dark:text-amber-200">{{ $nudge }}</flux:text>
                            @endif
                        </div>
                    @endif

                    <div class="text-center">
                        <flux:text class="text-zinc-500 text-sm">It means</flux:text>
                        <div class="font-medium">{{ $acceptedEnglish }}</div>
                    </div>

                    <flux:button wire:click="next" variant="primary" class="w-full" autofocus>Next</flux:button>
                </div>
            @endif
        </flux:card>
    @else
        <flux:card class="text-center space-y-3 py-10">
            <flux:heading size="xl">Nothing to practice yet</flux:heading>
            <flux:text>Ask a grown-up to add some cards first.</flux:text>
        </flux:card>
    @endif
</div>
