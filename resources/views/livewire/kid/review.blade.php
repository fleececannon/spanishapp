<div>
    <div class="mb-4 flex items-center justify-between">
        <flux:button :href="route('kid.home', ['tab' => $deck->value])" wire:navigate variant="ghost" size="sm" icon="home">Home</flux:button>
        <flux:button wire:click="logout" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
    </div>

    @if ($done)
        <flux:card class="text-center space-y-4 py-10">
            <div class="text-5xl">🎉</div>
            <flux:heading size="xl">All done for now!</flux:heading>
            <flux:text>You finished every card waiting for you. Come back later for more.</flux:text>
            <flux:button :href="route('kid.home', ['tab' => $deck->value])" wire:navigate variant="primary" class="!rounded-2xl">Back to home</flux:button>
        </flux:card>
    @elseif ($card)
        <flux:card class="space-y-5">
            <div class="flex items-center justify-between">
                <flux:text class="text-zinc-500 text-sm">{{ $remaining }} to go</flux:text>
                <flux:text class="text-zinc-500 text-sm">{{ $deck->label() }}</flux:text>
            </div>

            <div class="rounded-xl bg-zinc-100 dark:bg-zinc-800 p-6 text-center">
                <x-card-text :text="$deck->prompt($card)" size="xl" />
            </div>

            {{-- Say it out loud, reveal, then a grown-up marks it. --}}
            @if (! $revealed)
                <flux:button wire:click="reveal" variant="primary" class="w-full">Show answer</flux:button>
            @else
                <div class="text-center">
                    <flux:text class="text-zinc-500 text-sm">{{ $deck->answerLabel() }}</flux:text>
                    <x-card-text :text="$deck->answer($card)" class="mt-1" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <flux:button wire:click="mark(false)" variant="danger" icon="x-mark">Missed it</flux:button>
                    <flux:button wire:click="mark(true)" variant="primary" icon="check">Got it</flux:button>
                </div>
            @endif

            {{-- Pass on this one — nothing is marked wrong. --}}
            <flux:button wire:click="skip" variant="ghost" size="sm" class="w-full" icon="arrow-right">
                Skip for now
            </flux:button>
        </flux:card>
    @else
        <flux:card class="text-center space-y-3 py-10">
            <flux:heading size="xl">Nothing to practice yet</flux:heading>
            <flux:text>Ask a grown-up to add some cards first.</flux:text>
        </flux:card>
    @endif
</div>
