<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Pricing | Educore Management Systems</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-high": "#e7e7f1",
                        "on-primary-fixed-variant": "#21477b",
                        "tertiary-fixed-dim": "#ffb77b",
                        "on-surface": "#191b22",
                        "on-secondary-fixed": "#002109",
                        "primary-fixed-dim": "#a9c7ff",
                        "surface-bright": "#faf8ff",
                        "primary-fixed": "#d6e3ff",
                        "tertiary-container": "#653500",
                        "secondary-fixed-dim": "#84d991",
                        "error": "#ba1a1a",
                        "on-primary": "#ffffff",
                        "on-secondary-container": "#1b7337",
                        "inverse-primary": "#a9c7ff",
                        "surface-container-low": "#f2f3fd",
                        "on-secondary-fixed-variant": "#005322",
                        "on-tertiary-fixed": "#2e1500",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-highest": "#e1e2ec",
                        "on-tertiary": "#ffffff",
                        "background": "#faf8ff",
                        "surface-container": "#ededf7",
                        "inverse-surface": "#2e3038",
                        "secondary-fixed": "#9ff6ab",
                        "outline-variant": "#c3c6d1",
                        "on-surface-variant": "#43474f",
                        "surface-tint": "#3c5f95",
                        "surface": "#faf8ff",
                        "on-tertiary-container": "#e49e62",
                        "on-primary-fixed": "#001b3d",
                        "on-background": "#191b22",
                        "outline": "#737780",
                        "inverse-on-surface": "#f0f0fa",
                        "error-container": "#ffdad6",
                        "secondary": "#116d32",
                        "primary": "#002b59",
                        "primary-container": "#1a4175",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "secondary-container": "#9ff6ab",
                        "surface-dim": "#d9d9e3",
                        "on-primary-container": "#8caee9",
                        "tertiary": "#452200",
                        "tertiary-fixed": "#ffdcc2",
                        "on-error-container": "#93000a",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#e1e2ec",
                        "on-error": "#ffffff"
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .glass-header {
            backdrop-filter: blur(20px);
            background: rgba(250, 248, 255, 0.8);
        }

        .editorial-shadow {
            box-shadow: 0 10px 30px rgba(25, 27, 34, 0.06);
        }
    </style>
</head>
<body class="bg-background font-body text-on-surface">
    <nav class="fixed top-0 z-50 w-full bg-slate-50/80 shadow-sm backdrop-blur-lg dark:bg-slate-950/80">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <div class="hidden items-center gap-8 font-headline text-sm font-semibold tracking-tight md:flex">
                <a class="border-b-2 border-blue-700 pb-1 text-blue-700 dark:border-blue-300 dark:text-blue-300" href="{{ url('/pricing') }}">Pricing</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/faq') }}">FAQ</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/resources') }}">Resources</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="px-4 py-2 font-headline text-sm font-semibold text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/login') }}">Login</a>
                <a class="rounded-lg bg-primary px-6 py-2.5 font-headline text-sm font-semibold text-on-primary shadow-lg shadow-primary/10 transition-transform hover:-translate-y-[2px]" href="{{ url('/register') }}">Get Started</a>
            </div>
        </div>
        <div class="h-px bg-slate-200/20 dark:bg-slate-800/20"></div>
    </nav>

    <main class="pt-32">
        <section class="mx-auto mb-24 max-w-screen-xl px-6 text-center lg:px-12">
            <h1 class="mb-6 font-headline text-5xl font-extrabold tracking-tighter text-primary md:text-7xl">
                Invest in the future of Scholarship
            </h1>
            <p class="mx-auto max-w-2xl text-xl leading-relaxed text-on-surface-variant">
                Experience institutional excellence with transparent, performance-driven pricing. No hidden fees, no complex tiers. Just clarity.
            </p>
        </section>

        <section class="mx-auto mb-32 max-w-screen-xl px-6 lg:px-12">
            <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-3">
                <div class="editorial-shadow flex h-full flex-col rounded-xl bg-surface-container-lowest p-10 transition-transform hover:-translate-y-[4px]">
                    <div class="mb-8">
                        <h3 class="mb-2 font-headline text-lg font-bold text-on-surface">Essential</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-primary">$49</span>
                            <span class="font-medium text-on-surface-variant">/mo</span>
                        </div>
                        <p class="mt-4 text-sm text-on-surface-variant">For individual educators and small private tutors.</p>
                    </div>
                    <ul class="mb-10 flex-grow space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> Smart Attendance
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> Basic Gradebook
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> 256-bit Encryption
                        </li>
                    </ul>
                    <button class="w-full rounded-xl bg-surface-container-high py-4 font-headline font-bold text-on-surface transition-transform hover:-translate-y-[2px]">Choose Essential</button>
                </div>

                <div class="editorial-shadow relative z-10 flex h-full scale-100 flex-col overflow-hidden rounded-xl bg-primary p-10 text-on-primary shadow-2xl shadow-primary/20 transition-transform hover:-translate-y-[4px] md:scale-105">
                    <div class="absolute right-0 top-0 rounded-bl-xl bg-secondary-container px-6 py-1 font-headline text-xs font-bold uppercase tracking-widest text-on-secondary-container">Most Popular</div>
                    <div class="mb-8">
                        <h3 class="mb-2 font-headline text-lg font-bold">Professional</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold">$199</span>
                            <span class="font-medium opacity-80">/mo</span>
                        </div>
                        <p class="mt-4 text-sm opacity-80">For K-12 schools seeking total administrative control.</p>
                    </div>
                    <ul class="mb-10 flex-grow space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span> Unlimited Attendance
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span> Full Gradebook Plus
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span> Institutional Encryption
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span> Parent Portal
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary-container" style="font-variation-settings: 'FILL' 1;">check_circle</span> 24/7 Support
                        </li>
                    </ul>
                    <button class="w-full rounded-xl bg-secondary py-4 font-headline font-bold text-on-secondary transition-transform hover:-translate-y-[2px]">Upgrade Now</button>
                </div>

                <div class="editorial-shadow flex h-full flex-col rounded-xl bg-surface-container-lowest p-10 transition-transform hover:-translate-y-[4px]">
                    <div class="mb-8">
                        <h3 class="mb-2 font-headline text-lg font-bold text-on-surface">Institutional</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-primary">Custom</span>
                        </div>
                        <p class="mt-4 text-sm text-on-surface-variant">For large districts and prestigious universities.</p>
                    </div>
                    <ul class="mb-10 flex-grow space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> District-Wide API
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> Compliance Audit Logs
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> Dedicated Success Manager
                        </li>
                        <li class="flex items-center gap-3 text-sm font-medium">
                            <span class="material-symbols-outlined text-xl text-secondary">check_circle</span> White-Label Experience
                        </li>
                    </ul>
                    <button class="w-full rounded-xl bg-surface-container-high py-4 font-headline font-bold text-on-surface transition-transform hover:-translate-y-[2px]">Contact Sales</button>
                </div>
            </div>
        </section>

        <section class="mx-auto mb-32 max-w-screen-xl px-6 lg:px-12">
            <div class="mb-16">
                <h2 class="font-headline text-4xl font-bold tracking-tight text-primary">Uncompromising Quality as Standard</h2>
                <p class="mt-2 font-body text-on-surface-variant">Every feature is crafted for academic rigor and digital fluidity.</p>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-surface-container-low p-8 transition-all hover:bg-surface-container">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-primary-container">
                        <span class="material-symbols-outlined text-on-primary-container">qr_code_scanner</span>
                    </div>
                    <h4 class="mb-3 font-headline font-bold text-primary">Smart Attendance</h4>
                    <p class="text-sm leading-relaxed text-on-surface-variant">AI-driven patterns that predict absences before they occur. Seamless automation for the modern classroom.</p>
                </div>

                <div class="rounded-xl bg-surface-container-low p-8 transition-all hover:bg-surface-container">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-secondary-container">
                        <span class="material-symbols-outlined text-on-secondary-container">verified_user</span>
                    </div>
                    <h4 class="mb-3 font-headline font-bold text-primary">256-bit Encryption</h4>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Security is not a feature; it's our foundation. Student data is protected by military-grade security protocols.</p>
                </div>

                <div class="rounded-xl bg-surface-container-low p-8 transition-all hover:bg-surface-container">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-tertiary-fixed-dim">
                        <span class="material-symbols-outlined text-on-tertiary-fixed-variant">auto_graph</span>
                    </div>
                    <h4 class="mb-3 font-headline font-bold text-primary">Gradebook Mastery</h4>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Dynamic weighted grading and intuitive progress trackers. View student evolution across semesters instantly.</p>
                </div>

                <div class="rounded-xl bg-surface-container-low p-8 transition-all hover:bg-surface-container">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container-highest">
                        <span class="material-symbols-outlined text-primary">auto_stories</span>
                    </div>
                    <h4 class="mb-3 font-headline font-bold text-primary">The Editorial Experience</h4>
                    <p class="text-sm leading-relaxed text-on-surface-variant">Premium UI that feels like a curated journal. We prioritize legibility and focus to reduce administrative fatigue.</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-screen-xl px-6 lg:px-12">
            <div class="relative overflow-hidden rounded-3xl bg-primary-container p-10 md:p-16">
                <div class="absolute inset-0 opacity-10" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBiASzdp3k_a7Ao1bmCsogvLa4wV9JDAdkaqou3nli0iSwbiKRKocM90l9dnDKVgiPbM4XTArRA5WOhgnOpx8oX4Po7kybjrJjH9Itjy-9WiSJLfabi6uQmfD1bv9OR3ltJQZ6JIm355pkay-_G5bqn-TQP8nnsza31_MrW83cIHt4ahvSGvAm3eETCf2bdNK0vYJbx1CsNQ9hCW-CkMb8mmNWQrKmqO0gPu0e2YThsc0eV0WzBeMVu1SAmv_SmM9Xt2WasVjKjfxY0');"></div>
                <div class="relative z-10 flex flex-col items-center justify-between gap-12 md:flex-row">
                    <div class="max-w-xl text-center md:text-left">
                        <h2 class="mb-6 font-headline text-4xl font-extrabold text-white md:text-5xl">Ready to elevate your institution?</h2>
                        <p class="font-body text-lg leading-relaxed text-on-primary-container">Join 500+ forward-thinking schools that have already modernized their administrative ecosystem.</p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-4 sm:flex-row">
                        <a class="rounded-xl bg-secondary px-8 py-4 font-headline text-lg font-bold text-on-secondary shadow-xl shadow-secondary/20 transition-transform hover:-translate-y-[2px]" href="{{ url('/register') }}">Get Started Today</a>
                        <a class="rounded-xl border border-white/20 bg-white/10 px-8 py-4 font-headline text-lg font-bold text-white backdrop-blur-md transition-all hover:bg-white/20" href="{{ url('/request-demo') }}">Request a Demo</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="mt-24 flex flex-col items-center justify-between gap-6 border-t border-slate-200 bg-slate-50 px-6 py-8 md:flex-row lg:px-12">
        <div>
            <div class="mb-2 font-headline text-lg font-bold text-blue-900">Educore</div>
            <div class="font-body text-xs text-slate-500">&copy; 2024 Educore Systems. All rights reserved.</div>
        </div>
        <div class="flex flex-wrap justify-center gap-8 font-body text-xs text-slate-500">
            <a class="transition-all hover:text-blue-600 hover:underline" href="#">Privacy Policy</a>
            <a class="transition-all hover:text-blue-600 hover:underline" href="#">Terms of Service</a>
            <a class="transition-all hover:text-blue-600 hover:underline" href="#">Security</a>
            <a class="transition-all hover:text-blue-600 hover:underline" href="#">Cookie Policy</a>
        </div>
    </footer>
</body>
</html>
