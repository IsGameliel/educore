<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Email Service Unavailable' }} | {{ config('app.name', 'EduCore') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4efe6;
            --panel: rgba(255, 255, 255, 0.82);
            --panel-border: rgba(122, 89, 46, 0.16);
            --text: #1f2937;
            --muted: #5b6472;
            --accent: #b45309;
            --accent-deep: #7c2d12;
            --accent-soft: #fde7c7;
            --shadow: 0 24px 70px rgba(84, 55, 20, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(180, 83, 9, 0.16), transparent 35%),
                radial-gradient(circle at bottom right, rgba(124, 45, 18, 0.12), transparent 28%),
                linear-gradient(135deg, #fbf7f0 0%, var(--bg) 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(100%, 1040px);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            overflow: hidden;
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .copy,
        .aside {
            padding: 48px;
        }

        .copy {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(255, 248, 240, 0.9));
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-deep);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 22px 0 16px;
            font-size: clamp(2.4rem, 5vw, 4.5rem);
            line-height: 0.95;
            letter-spacing: -0.04em;
        }

        p {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.04rem;
            line-height: 1.8;
            color: var(--muted);
        }

        .lead {
            max-width: 36rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 32px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            border-radius: 999px;
            text-decoration: none;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: #fffaf2;
            background: linear-gradient(135deg, var(--accent), #c2410c);
            box-shadow: 0 18px 36px rgba(180, 83, 9, 0.22);
        }

        .button-secondary {
            color: var(--accent-deep);
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(124, 45, 18, 0.12);
        }

        .aside {
            position: relative;
            display: grid;
            align-content: center;
            gap: 22px;
            background:
                linear-gradient(180deg, rgba(124, 45, 18, 0.95), rgba(120, 53, 15, 0.9)),
                linear-gradient(135deg, #7c2d12, #9a3412);
            color: #fff7ed;
        }

        .orb {
            position: absolute;
            border-radius: 999px;
            opacity: 0.28;
        }

        .orb-one {
            width: 210px;
            height: 210px;
            top: -60px;
            right: -40px;
            background: #fdba74;
        }

        .orb-two {
            width: 160px;
            height: 160px;
            bottom: 28px;
            left: -40px;
            background: #fcd34d;
        }

        .status {
            position: relative;
            width: 132px;
            height: 132px;
            display: grid;
            place-items: center;
            border-radius: 32px;
            background: rgba(255, 247, 237, 0.08);
            border: 1px solid rgba(255, 247, 237, 0.16);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 3rem;
            font-weight: 800;
        }

        .aside h2 {
            position: relative;
            margin: 0;
            font-size: 1.5rem;
            letter-spacing: -0.03em;
        }

        .aside p,
        .tips li {
            position: relative;
            color: rgba(255, 247, 237, 0.88);
        }

        .tips {
            position: relative;
            margin: 0;
            padding-left: 20px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
        }

        .tips li + li {
            margin-top: 6px;
        }

        @media (max-width: 860px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .copy,
            .aside {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="copy">
            <div class="eyebrow">Service interruption</div>
            <h1>{{ $title ?? 'Email Service Unavailable' }}</h1>
            <p class="lead">
                {{ $message ?? 'We could not send your email because the mail service is temporarily unavailable.' }}
            </p>

            <div class="actions">
                <a href="{{ url()->previous() }}" class="button button-primary">Try Again</a>
                <a href="{{ url('/') }}" class="button button-secondary">Return Home</a>
            </div>
        </section>

        <aside class="aside">
            <div class="orb orb-one"></div>
            <div class="orb orb-two"></div>
            <div class="status">503</div>
            <h2>Your request was received, but the email could not be delivered.</h2>
            <p>{{ $hint ?? 'Please try again shortly. If this continues, review the mail server settings and confirm the app can reach your SMTP host.' }}</p>
            <ul class="tips">
                <li>Check the SMTP host, port, username, and encryption settings in `.env`.</li>
                <li>Make sure this machine can resolve and reach the configured mail server.</li>
                <li>Use a mail sandbox or queue worker in development when appropriate.</li>
            </ul>
        </aside>
    </main>
</body>
</html>
