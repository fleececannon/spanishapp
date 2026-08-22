<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lesson {{ $lesson->position }} · {{ $lesson->title }}</title>
    @include('partials.lesson-style')
    <style>
        body { margin: 0 auto; max-width: 7.6in; padding: 24px; background: #fff; }
        .lesson-sheet { padding: 0; max-width: none; }
        .print-button { position: fixed; top: 16px; right: 16px; font: bold 14px system-ui, sans-serif;
            background: #6366f1; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; cursor: pointer; }
        @media print { .print-button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print</button>
    <div class="lesson-sheet">
        <div class="sheet-title">Lección {{ $lesson->position }} · {{ $lesson->title }}</div>
        <div class="sheet-sub">@if ($lesson->minutes)~{{ $lesson->minutes }} minutes · @endif{{ $lesson->subtitle }}</div>
        {!! $lesson->body !!}
    </div>
</body>
</html>
