<style>
    /* Lesson sheet — deliberately print-like (white paper, dark ink) in both app themes. */
    .lesson-sheet { --ink: #1a1a2e; --accent: #6366f1; --soft: #eef0ff; --muted: #6b7280;
        background: #fff; color: var(--ink); font-family: Georgia, 'Times New Roman', serif;
        line-height: 1.45; padding: 32px 36px; border-radius: 12px; max-width: 52rem; }
    .lesson-sheet .sheet-title { font-size: 26px; font-weight: bold; margin-bottom: 2px; }
    .lesson-sheet .sheet-sub { color: var(--muted); font-size: 14px; margin-bottom: 18px; }
    .lesson-sheet h2 { font-size: 15px; text-transform: uppercase; letter-spacing: .08em; color: var(--accent);
        border-bottom: 2px solid var(--accent); padding-bottom: 3px; margin: 22px 0 10px; font-weight: bold; }
    .lesson-sheet h2 .time { float: right; font-weight: normal; color: var(--muted); font-size: 12px; letter-spacing: 0; text-transform: none; }
    .lesson-sheet table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .lesson-sheet td { padding: 5px 8px; vertical-align: top; border-bottom: 1px solid #e5e7eb; }
    .lesson-sheet td.you { width: 46%; font-weight: bold; }
    .lesson-sheet td.kid { width: 54%; }
    .lesson-sheet .en { display: block; font-size: 11.5px; color: var(--muted); font-style: italic; font-weight: normal; }
    .lesson-sheet .round { font-size: 13px; color: var(--muted); margin: 10px 0 6px; }
    .lesson-sheet .rules { background: var(--soft); border-left: 4px solid var(--accent); padding: 10px 14px;
        font-size: 13px; margin: 12px 0; border-radius: 0 6px 6px 0; }
    .lesson-sheet .swap { background: #fff7ed; border-left: 4px solid #f59e0b; padding: 10px 14px;
        font-size: 13px; margin: 12px 0; border-radius: 0 6px 6px 0; }
    .lesson-sheet .bank { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 8px; }
    .lesson-sheet .bank > div { flex: 1 1 160px; background: #fafafa; border: 1px solid #e5e7eb;
        border-radius: 8px; padding: 10px 12px; font-size: 12.5px; }
    .lesson-sheet .bank h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .06em;
        color: var(--accent); margin: 0 0 5px; font-weight: bold; }
    .lesson-sheet .bank span { color: var(--muted); }
    @media print {
        .lesson-sheet { padding: 0; border-radius: 0; max-width: none; }
        .lesson-sheet .rules, .lesson-sheet .swap, .lesson-sheet .bank > div {
            -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .lesson-sheet h2 { break-after: avoid; }
        .lesson-sheet table { break-inside: avoid; }
    }
</style>
