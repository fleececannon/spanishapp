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
            width: 2.4in; height: 4in; border: 1px dashed #999;
            display: flex; flex-direction: column; break-inside: avoid;
            background: #fff;
        }
        .half {
            height: 50%; display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 0.15in; overflow: hidden;
        }
        .front { border-bottom: 1px dotted #bbb; font-size: 21px; font-weight: bold; }
        .back { transform: rotate(180deg); font-size: 15px; color: #333; }

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
            <p>Print, cut along the dashed lines, then fold each card on the dotted middle line — Spanish ends up on the front, English on the back.</p>
        </div>
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="grid">
            @foreach ($cards as $card)
                <div class="card">
                    <div class="half front">{{ $card->spanish }}</div>
                    <div class="half back">{{ $card->english }}</div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
