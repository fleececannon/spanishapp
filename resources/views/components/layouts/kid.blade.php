<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-zinc-50 dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        {{-- my-auto centers short content but stays scrollable when content
             is taller than the screen (justify-center would clip the top). --}}
        <div class="flex min-h-svh flex-col items-center gap-6 p-6">
            <div class="w-full max-w-xl my-auto">
                {{ $slot }}
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
