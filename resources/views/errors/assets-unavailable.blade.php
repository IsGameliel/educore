<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Frontend Assets Unavailable' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f4ec;
            --card: #fffdf8;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #b45309;
            --accent-dark: #92400e;
            --border: #eadfcb;
            --shadow: 0 24px 60px rgba(120, 53, 15, 0.14);
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
                radial-gradient(circle at top, rgba(180, 83, 9, 0.12), transparent 35%),
                linear-gradient(180deg, #fffcf5 0%, var(--bg) 100%);
        }

        .card {
            width: min(100%, 700px);
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
            background: rgba(180, 83, 9, 0.12);
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

        .hint {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 16px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }

        code {
            font-family: "Courier New", Courier, monospace;
            font-size: 0.95em;
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
            background: #f3eadb;
        }

        .button-secondary:hover {
            background: #eadfcb;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Build Required</span>
        <h1>{{ $title ?? 'Frontend Assets Unavailable' }}</h1>
        <p>{{ $message ?? 'The compiled frontend assets are missing, so this page cannot be rendered as expected.' }}</p>

        <div class="hint">
            {{ $hint ?? 'Run "npm run build" or start the Vite dev server with "npm run dev", then refresh this page.' }}
        </div>

        <div class="actions">
            <a href="{{ url('/') }}" class="button button-primary">Try Again</a>
            <a href="javascript:history.back()" class="button button-secondary">Go Back</a>
        </div>
    </main>
</body>
</html>
