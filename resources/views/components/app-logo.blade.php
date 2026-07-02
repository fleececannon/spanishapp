@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Amani Spanish App" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-sm">
            <x-app-logo-icon class="size-5 text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Amani Spanish App" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-sm">
            <x-app-logo-icon class="size-5 text-white" />
        </x-slot>
    </flux:brand>
@endif
