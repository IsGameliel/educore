<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Service Temporarily Unavailable' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --border: #dbe4ee;
            --shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(15, 118, 110, 0.10), transparent 35%),
                linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
        }

        .card {
            width: min(100%, 640px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 40px 32px;
        }

        .badge {
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent-dark);
            background: rgba(15, 118, 110, 0.10);
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(30px, 5vw, 42px);
            line-height: 1.1;
        }

        p {
            margin: 0;
            font-size: 16px;
            line-height: 1.7;
            color: var(--muted);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .button-primary {
            color: #ffffff;
            background: var(--accent);
        }

        .button-primary:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        .button-secondary {
            color: var(--text);
            background: #eef2f7;
        }

        .button-secondary:hover {
            background: #e5ebf3;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Database Unavailable</span>
        <h1>{{ $title ?? 'Service Temporarily Unavailable' }}</h1>
        <p>{{ $message ?? 'We are having trouble connecting to the database right now. Please try again shortly.' }}</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="button button-primary">Try Again</a>
            <a href="javascript:history.back()" class="button button-secondary">Go Back</a>
        </div>
    </main>
</body>
</html>
