<div class="mx-auto max-w-xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">My Practice</flux:heading>
            @unless ($done)
                <flux:text class="mt-1">{{ $remaining }} to go</flux:text>
            @endunless
        </div>
        <flux:button :href="route('admin.my-verbs')" wire:navigate variant="ghost" icon="queue-list">My Verbs</flux:button>
    </div>

    @if ($done)
        <flux:card class="py-16 text-center">
            <div class="text-5xl">🎉</div>
            <flux:heading size="lg" class="mt-4">All done for today</flux:heading>
            <flux:text class="mt-2">Nothing due. Unlock more verbs when you want a bigger queue.</flux:text>
            <div class="mt-6">
                <flux:button :href="route('admin.my-verbs')" wire:navigate variant="primary">Go to My Verbs</flux:button>
            </div>
        </flux:card>
    @elseif ($verb)
        <flux:card class="py-12 text-center">
            <div class="text-4xl font-bold tracking-tight">{{ $verb->spanish }}</div>

            @if ($revealed)
                <div class="mt-4 text-xl text-zinc-500">{{ $verb->english }}</div>
                <div class="mt-8 flex justify-center gap-3">
                    <flux:button wire:click="mark(false)" variant="danger" size="base">Missed it</flux:button>
                    <flux:button wire:click="mark(true)" variant="primary" size="base">Got it</flux:button>
                </div>
            @else
                <div class="mt-8">
                    <flux:button wire:click="reveal" variant="primary" size="base">Show answer</flux:button>
                </div>
            @endif

            <div class="mt-6">
                <flux:button wire:click="skip" variant="ghost" size="sm">Skip for now</flux:button>
            </div>
        </flux:card>
    @endif
</div>
