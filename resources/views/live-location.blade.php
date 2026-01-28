<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $label }} · Live Location</title>
        <meta property="og:title" content="{{ $label }}">
        <meta property="og:description" content="Updated {{ $timestamp }} · {{ $coordinates }}">
        <meta property="og:image" content="{{ $thumbnailUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $mapsUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $label }}">
        <meta name="twitter:description" content="Updated {{ $timestamp }} · {{ $coordinates }}">
        <meta name="twitter:image" content="{{ $thumbnailUrl }}">
        <meta http-equiv="refresh" content="0;url={{ $mapsUrl }}">
        <style>
            body {
                font-family: "Inter", "Segoe UI", Arial, sans-serif;
                background: #0f172a;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 32px;
                text-align: center;
            }

            .card {
                max-width: 560px;
                background: rgba(15, 23, 42, 0.85);
                border: 1px solid rgba(148, 163, 184, 0.2);
                border-radius: 16px;
                padding: 32px;
                box-shadow: 0 20px 40px rgba(15, 23, 42, 0.4);
            }

            h1 {
                font-size: 32px;
                margin: 0 0 12px;
            }

            p {
                margin: 8px 0;
                color: #cbd5f5;
            }

            a {
                color: #38bdf8;
                text-decoration: none;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>{{ $label }}</h1>
            <p>Updated {{ $timestamp }}</p>
            <p>Coordinates: {{ $coordinates }}</p>
            <p><a href="{{ $mapsUrl }}">Open in Google Maps</a></p>
        </div>
    </body>
</html>
