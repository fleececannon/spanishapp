<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Lesson {{ $lesson->position }} · {{ $lesson->title }}</flux:heading>
            @if ($lesson->subtitle)
                <flux:text class="mt-1">{{ $lesson->subtitle }}</flux:text>
            @endif
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.lessons')" wire:navigate variant="ghost" icon="arrow-left">All lessons</flux:button>
            <flux:button :href="route('admin.lessons.print', $lesson)" target="_blank" variant="primary" icon="printer">Print</flux:button>
        </div>
    </div>

    @include('partials.lesson-style')

    <div class="lesson-sheet shadow-lg ring-1 ring-zinc-200 dark:ring-zinc-700">
        <div class="sheet-title">Lección {{ $lesson->position }} · {{ $lesson->title }}</div>
        <div class="sheet-sub">@if ($lesson->minutes)~{{ $lesson->minutes }} minutes · @endif{{ $lesson->subtitle }}</div>
        {!! $lesson->body !!}
    </div>
</div>
