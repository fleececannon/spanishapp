@props(['text', 'size' => 'lg'])

{{-- One side of a card. A question-and-reply side is shown as two labelled
     lines; anything else is shown as it is. --}}
@php
    $lines = \App\Enums\Direction::lines($text);
    $textClass = match ($size) {
        'xl' => 'text-2xl font-semibold',
        'sm' => 'text-sm',
        default => 'text-lg font-medium',
    };
@endphp

@if (count($lines) === 2)
    <div {{ $attributes->class('inline-grid grid-cols-[auto_1fr] items-baseline gap-x-3 gap-y-2 text-left') }}>
        @foreach ($lines as $line)
            <span class="text-sm font-bold uppercase text-indigo-500 dark:text-indigo-400" aria-label="{{ $loop->first ? 'Question' : 'Answer' }}">{{ $loop->first ? 'Q' : 'A' }}</span>
            <span class="{{ $textClass }}">{{ $line }}</span>
        @endforeach
    </div>
@else
    <div {{ $attributes->class('space-y-1') }}>
        @foreach ($lines as $line)
            <div class="{{ $textClass }}">{{ $line }}</div>
        @endforeach
    </div>
@endif
