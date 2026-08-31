<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 2px; color: #1E5631; }
        .meta { color: #666; font-size: 9px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; font-size: 9px; }
        th { background: #1E5631; color: #fff; }
        tr:nth-child(even) td { background: #f4f5f3; }
        .summary { margin-bottom: 12px; }
        .summary td { border: none; padding: 2px 12px 2px 0; font-size: 10px; }
        .summary td.label { color: #666; }
        .summary td.value { font-weight: bold; }
    </style>
</head>
<body>
    <h1>CDP Credix &mdash; {{ $title }}</h1>
    <div class="meta">
        Filters: {{ $filters }} &nbsp;|&nbsp; Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    @yield('content')
</body>
</html>
