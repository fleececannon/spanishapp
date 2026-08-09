<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Print vocab cards — {{ ucfirst($type) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; color: #111; background: #f4f4f5; }

        .toolbar {
            position: sticky; top: 0; background: #fff; border-bottom: 1px solid #ddd;
            padding: 16px 24px; display: flex; align-items: center; gap: 16px;
            font-family: system-ui, sans-serif;
        }
        .toolbar h1 { font-size: 18px; }
        .toolbar p { color: #555; font-size: 13px; }
        .toolbar button {
            margin-left: auto; background: #18181b; color: #fff; border: 0;
            padding: 10px 18px; border-radius: 8px; font-size: 14px; cursor: pointer;
        }

        .sheet { max-width: 8.5in; margin: 0 auto; padding: 0.5in 0; }
        .grid {
            display: grid; grid-template-columns: repeat(3, 2.4in);
            gap: 0.12in; justify-content: center;
        }
        .card {
            width: 2.4in; height: 1.8in; border: 1px dashed #999;
            display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 0.15in; overflow: hidden;
            break-inside: avoid; background: #fff;
            font-size: 22px; font-weight: bold;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { padding: 0; max-width: none; }
        }
        @page { size: letter; margin: 0.5in; }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>{{ $cards->count() }} vocab cards — {{ $type === 'all' ? 'words & verbs' : $type }}</h1>
            <p>Spanish only, one-sided — print, then cut along the dashed lines.</p>
        </div>
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="grid">
            @foreach ($cards as $card)
                <div class="card">{{ $card->spanish }}</div>
            @endforeach
        </div>
    </div>
</body>
</html>
