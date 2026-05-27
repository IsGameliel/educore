<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Reset Password | Educore Management Systems</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Inter:wght@100..900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container": "#ededf7",
                        "inverse-primary": "#a9c7ff",
                        "on-error": "#ffffff",
                        "tertiary": "#452200",
                        "primary-container": "#1a4175",
                        "surface-container-lowest": "#ffffff",
                        "tertiary-fixed-dim": "#ffb77b",
                        "surface-bright": "#faf8ff",
                        "outline": "#737780",
                        "surface-variant": "#e1e2ec",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "on-background": "#191b22",
                        "on-secondary": "#ffffff",
                        "primary-fixed": "#d6e3ff",
                        "surface-container-high": "#e7e7f1",
                        "surface-dim": "#d9d9e3",
                        "primary-fixed-dim": "#a9c7ff",
                        "outline-variant": "#c3c6d1",
                        "on-surface": "#191b22",
                        "primary": "#002b59",
                        "error-container": "#ffdad6",
                        "on-secondary-fixed-variant": "#005322",
                        "on-secondary-fixed": "#002109",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#8caee9",
                        "error": "#ba1a1a",
                        "background": "#faf8ff",
                        "on-secondary-container": "#1b7337",
                        "surface-container-highest": "#e1e2ec",
                        "on-tertiary-container": "#e49e62",
                        "surface": "#faf8ff",
                        "surface-tint": "#3c5f95",
                        "tertiary-container": "#653500",
                        "on-primary-fixed-variant": "#21477b",
                        "on-surface-variant": "#43474f",
                        "inverse-surface": "#2e3038",
                        "on-primary-fixed": "#001b3d",
                        "secondary-fixed": "#9ff6ab",
                        "surface-container-low": "#f2f3fd",
                        "tertiary-fixed": "#ffdcc2",
                        "on-error-container": "#93000a",
                        "inverse-on-surface": "#f0f0fa",
                        "secondary-fixed-dim": "#84d991",
                        "secondary": "#116d32",
                        "secondary-container": "#9ff6ab",
                        "on-tertiary-fixed": "#2e1500",
                        "on-tertiary": "#ffffff"
                    },
                    borderRadius: {
                        DEFAULT: "0.125rem",
                        lg: "0.25rem",
                        xl: "0.5rem",
                        full: "0.75rem"
                    },
                    fontFamily: {
                        headline: ["Manrope"],
                        body: ["Inter"],
                        label: ["Inter"]
                    }
                }
            }
        };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-headline { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-surface text-on-surface">
    <nav class="fixed top-0 z-50 w-full bg-slate-50/80 shadow-sm backdrop-blur-md transition-all duration-300 ease-in-out dark:bg-slate-950/80 dark:shadow-none">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
            <a class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-[#002b59] font-headline dark:text-blue-100" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <div class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="rounded-lg px-3 py-2 text-slate-600 transition-colors hover:bg-slate-100/50 hover:text-blue-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-blue-100" href="{{ url('/pricing') }}">Pricing</a>
                <a class="rounded-lg px-3 py-2 text-slate-600 transition-colors hover:bg-slate-100/50 hover:text-blue-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-blue-100" href="{{ url('/faq') }}">FAQ</a>
                <a class="rounded-lg px-3 py-2 text-slate-600 transition-colors hover:bg-slate-100/50 hover:text-blue-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-blue-100" href="{{ url('/resources') }}">Resources</a>
                <a class="rounded-lg px-3 py-2 text-slate-600 transition-colors hover:bg-slate-100/50 hover:text-blue-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-blue-100" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="rounded-lg px-4 py-2 font-['Manrope'] text-sm font-semibold tracking-tight text-blue-900 transition-all hover:bg-slate-100/50 dark:text-blue-400 dark:hover:bg-slate-800/50" href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </nav>

    <main class="relative flex flex-grow items-center justify-center overflow-hidden bg-surface px-6 pb-12 pt-24">
        <div class="absolute right-[-5%] top-[-10%] h-[60%] w-[40%] rounded-full bg-primary-container/5 blur-3xl"></div>
        <div class="absolute bottom-[-10%] left-[-5%] h-[50%] w-[30%] rounded-full bg-secondary-container/10 blur-3xl"></div>

        <div class="z-10 w-full max-w-[520px]">
            <div class="rounded-xl border border-outline-variant/15 bg-surface-container-lowest p-8 shadow-[0_10px_30px_rgba(25,27,34,0.06)] md:p-12">
                <div class="mb-10 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-fixed text-primary shadow-lg shadow-primary/10">
                        <span class="material-symbols-outlined text-3xl">mark_email_read</span>
                    </div>
                    <h1 class="mb-2 font-headline text-4xl font-extrabold tracking-tighter text-primary">Password recovery</h1>
                    <p class="mx-auto max-w-md text-sm font-medium text-on-surface-variant">
                        Enter your email address and we will send a reset link so you can regain access to your academic workspace.
                    </p>
                </div>

                @session('status')
                    <div class="mb-4 rounded-lg border border-secondary-container bg-secondary-container/30 px-4 py-3 text-sm font-medium text-on-secondary-container">
                        {{ $value }}
                    </div>
                @endsession

                <x-validation-errors class="mb-4 rounded-lg border border-error-container bg-error-container px-4 py-3 text-sm text-on-error-container" />

                <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="space-y-2">
                        <label class="block font-label text-sm font-semibold text-on-surface-variant" for="email">Email address</label>
                        <input class="w-full rounded-lg border-none border-b-2 border-transparent bg-surface-container-highest px-4 py-3.5 text-on-surface placeholder:text-outline transition-all focus:border-primary focus:ring-0 focus:ring-offset-0" id="email" name="email" placeholder="registrar@educore.edu" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                    </div>

                    <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-primary to-primary-container py-4 font-headline font-bold text-on-primary shadow-lg shadow-primary/10 transition-all hover:-translate-y-0.5 active:translate-y-0" type="submit">
                        <span>Send Reset Link</span>
                        <span class="material-symbols-outlined text-xl">outgoing_mail</span>
                    </button>
                </form>

                <div class="mt-8 rounded-xl border border-outline-variant/15 bg-surface-container-low px-4 py-4 text-sm text-on-surface-variant">
                    If you remember your password, you can return to
                    <a class="font-semibold text-primary hover:underline decoration-2 underline-offset-4" href="{{ route('login') }}">the login page</a>.
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <div class="flex max-w-fit items-center gap-2 rounded-full border border-tertiary-container/20 bg-tertiary-container/10 px-4 py-2">
                    <span class="material-symbols-outlined text-sm text-tertiary-container">shield_lock</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-tertiary-container">Secure Recovery Flow</span>
                </div>
            </div>
        </div>
    </main>

    <footer class="w-full border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-8 py-12 md:flex-row">
            <div class="font-['Manrope'] font-black text-blue-900 dark:text-blue-100">EduCore</div>
            <div class="flex gap-6 font-['Inter'] text-xs font-medium">
                <a class="text-slate-500 transition-colors hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-200" href="#">Privacy Policy</a>
                <a class="text-slate-500 transition-colors hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-200" href="#">Terms of Service</a>
                <a class="text-slate-500 transition-colors hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-200" href="#">Security</a>
                <a class="text-slate-500 transition-colors hover:text-blue-800 dark:text-slate-400 dark:hover:text-blue-200" href="#">Accessibility</a>
            </div>
            <p class="font-['Inter'] text-xs font-medium text-slate-500 dark:text-slate-400">&copy; 2024 Educore Management Systems. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
