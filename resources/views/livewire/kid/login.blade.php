<div>
    {{-- Brand lockup --}}
    <div class="mb-8 flex flex-col items-center gap-4 text-center">
        <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-indigo-500 to-purple-600 shadow-xl shadow-purple-600/30">
            <x-app-logo-icon class="size-12 text-white" />
        </div>
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Amani Spanish</h1>
            <p class="mt-1 text-lg text-zinc-500 dark:text-zinc-400">¡Hola! 👋 Ready to practice?</p>
        </div>
    </div>

    <flux:card class="space-y-6 rounded-3xl border-none shadow-xl">
        <form wire:submit="login" class="space-y-5">
            <flux:select wire:model="name" label="Who are you?" placeholder="Choose your name" size="lg">
                @foreach ($kids as $kidName)
                    <flux:select.option value="{{ $kidName }}">{{ $kidName }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="password" type="password" label="Password" placeholder="••••••" size="lg" />

            <flux:button type="submit" variant="primary" class="w-full !h-14 !rounded-2xl !text-lg !font-bold">
                Let's go! 🚀
            </flux:button>
        </form>
    </flux:card>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" wire:navigate
            class="text-sm font-medium text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
            I'm a grown-up →
        </a>
    </div>
</div>
