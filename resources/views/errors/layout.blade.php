<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Greška') · SuperSkyLaw</title>
    <style>
        :root {
            --sluzbena-zelena: #b0cb1f;
            --sluzbena-tamna: #434d0c;
            --sluzbena-svijetla: #f7fae9;
            --sluzbena-tinta: #272d07;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: "Segoe UI", system-ui, sans-serif;
            font-size: 13px;
            background: var(--sluzbena-svijetla);
            color: var(--sluzbena-tinta);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .error-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            border-top: 4px solid var(--sluzbena-zelena);
            box-shadow: 0 12px 30px rgba(39, 45, 7, 0.08);
            padding: 28px 24px 22px;
            text-align: center;
        }
        .error-brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 650;
            color: var(--sluzbena-tinta);
            font-size: 13px;
            margin-bottom: 16px;
        }
        .error-brand-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--sluzbena-zelena);
            display: inline-block;
        }
        .error-code {
            font-size: 40px;
            font-weight: 700;
            line-height: 1;
            color: var(--sluzbena-tamna);
            margin: 0 0 8px;
        }
        .error-heading {
            font-size: 18px;
            font-weight: 650;
            margin: 0 0 8px;
            color: var(--sluzbena-tinta);
        }
        .error-message {
            font-size: 13px;
            line-height: 1.5;
            color: var(--sluzbena-tamna);
            margin: 0 auto 18px;
            max-width: 36ch;
        }
        .error-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .error-btn {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .error-btn--primary {
            background: var(--sluzbena-tamna);
            border-color: var(--sluzbena-tamna);
            color: #fff;
        }
        .error-btn--primary:hover { filter: brightness(1.08); }
        .error-btn--ghost {
            background: #fff;
            color: var(--sluzbena-tinta);
            border-color: #c5c5c5;
        }
        .error-btn--ghost:hover { border-color: #8d8d8d; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-brand"><span class="error-brand-dot"></span> SuperSkyLaw</div>
        <p class="error-code">@yield('code')</p>
        <h1 class="error-heading">@yield('heading')</h1>
        <p class="error-message">@yield('message')</p>
        <div class="error-actions">
            @yield('actions')
            <a class="error-btn error-btn--primary" href="{{ url('/') }}">Natrag na početnu</a>
        </div>
    </div>
</body>
</html>
