<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Support | Educore Management Systems</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "tertiary-fixed": "#ffdcc2",
                        "outline-variant": "#c3c6d1",
                        "error-container": "#ffdad6",
                        "tertiary-fixed-dim": "#ffb77b",
                        "surface-container-high": "#e7e7f1",
                        "on-primary-container": "#8caee9",
                        "background": "#faf8ff",
                        "on-tertiary": "#ffffff",
                        "surface-container-low": "#f2f3fd",
                        "on-secondary-fixed": "#002109",
                        "on-error": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-container": "#1b7337",
                        "on-secondary": "#ffffff",
                        "secondary-container": "#9ff6ab",
                        "surface-container": "#ededf7",
                        "on-tertiary-fixed": "#2e1500",
                        "on-tertiary-container": "#e49e62",
                        "surface-dim": "#d9d9e3",
                        "tertiary-container": "#653500",
                        "secondary": "#116d32",
                        "surface": "#faf8ff",
                        "on-surface": "#191b22",
                        "on-primary": "#ffffff",
                        "on-background": "#191b22",
                        "surface-tint": "#3c5f95",
                        "on-primary-fixed-variant": "#21477b",
                        "secondary-fixed-dim": "#84d991",
                        "inverse-primary": "#a9c7ff",
                        "secondary-fixed": "#9ff6ab",
                        "surface-variant": "#e1e2ec",
                        "primary-container": "#1a4175",
                        "surface-bright": "#faf8ff",
                        "primary-fixed-dim": "#a9c7ff",
                        "on-surface-variant": "#43474f",
                        "inverse-on-surface": "#f0f0fa",
                        "on-secondary-fixed-variant": "#005322",
                        "primary": "#002b59",
                        "primary-fixed": "#d6e3ff",
                        "surface-container-highest": "#e1e2ec",
                        "inverse-surface": "#2e3038",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "tertiary": "#452200",
                        "on-error-container": "#93000a",
                        "outline": "#737780",
                        "on-primary-fixed": "#001b3d",
                        "error": "#ba1a1a"
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
        h1, h2, h3, .font-headline { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-surface text-on-surface selection:bg-primary-fixed selection:text-on-primary-fixed">
    <nav class="fixed top-0 z-50 w-full bg-[#faf8ff]/80 shadow-[0_10px_30px_rgba(25,27,34,0.06)] backdrop-blur-xl dark:bg-[#191b22]/80">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-8 py-4">
            <a class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-[#1A4175] dark:text-[#f2f3fd] font-headline" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <div class="hidden items-center gap-8 font-['Manrope'] font-semibold tracking-tight md:flex">
                <a class="text-[#191b22]/70 transition-transform duration-200 hover:-translate-y-[2px] dark:text-[#faf8ff]/70" href="{{ url('/pricing') }}">Pricing</a>
                <a class="text-[#191b22]/70 transition-transform duration-200 hover:-translate-y-[2px] dark:text-[#faf8ff]/70" href="{{ url('/faq') }}">FAQ</a>
                <a class="text-[#191b22]/70 transition-transform duration-200 hover:-translate-y-[2px] dark:text-[#faf8ff]/70" href="{{ url('/resources') }}">Resources</a>
                <a class="border-b-2 border-[#1A4175] pb-1 text-[#1A4175] transition-transform duration-200 hover:-translate-y-[2px] dark:text-white" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="px-5 py-2 text-sm font-semibold text-[#1A4175] transition-transform hover:-translate-y-[2px]" href="{{ url('/login') }}">Login</a>
                <a class="rounded-xl bg-primary-container px-6 py-2.5 text-sm font-bold text-white shadow-lg transition-transform duration-200 hover:-translate-y-[2px] active:scale-95" href="{{ url('/register') }}">Get Started</a>
            </div>
        </div>
    </nav>
    <main class="pt-24">
        <section class="relative overflow-hidden px-8 py-24 md:py-32">
            <div class="pointer-events-none absolute inset-0 z-0 opacity-10">
                <div class="absolute top-0 right-0 -mr-48 -mt-48 h-[600px] w-[600px] rounded-full bg-primary blur-[120px]"></div>
                <div class="absolute bottom-0 left-0 -mb-24 -ml-24 h-[400px] w-[400px] rounded-full bg-secondary blur-[100px]"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-4xl text-center">
                <h1 class="mb-8 text-5xl font-extrabold font-headline tracking-tighter text-primary md:text-6xl">
                    How can we help you today?
                </h1>
                <div class="group relative">
                    <div class="pointer-events-none absolute inset-y-0 left-6 flex items-center text-outline">
                        <span class="material-symbols-outlined">search</span>
                    </div>
                    <input class="w-full rounded-full border-none bg-surface-container-lowest py-6 pl-16 pr-8 text-lg shadow-xl outline-none transition-all placeholder:text-outline-variant focus:ring-4 focus:ring-primary/5" placeholder="Search for documentation, guides, or specific features..." type="text" />
                    <button class="absolute inset-y-2.5 right-2.5 rounded-full bg-primary-container px-8 font-bold text-white transition-transform hover:-translate-y-[1px] active:scale-95">
                        Search
                    </button>
                </div>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <span class="text-sm font-medium text-on-surface-variant">Popular:</span>
                    <a class="text-sm font-semibold text-primary hover:underline decoration-2 underline-offset-4" href="#">Gradebook Setup</a>
                    <a class="text-sm font-semibold text-primary hover:underline decoration-2 underline-offset-4" href="#">Student Onboarding</a>
                    <a class="text-sm font-semibold text-primary hover:underline decoration-2 underline-offset-4" href="#">API Documentation</a>
                </div>
            </div>
        </section>
        <section class="mx-auto max-w-screen-2xl px-8 pb-32">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <a class="group cursor-pointer rounded-xl border border-outline-variant/10 bg-surface-container-lowest p-10 shadow-[0_10px_30px_rgba(25,27,34,0.04)] transition-all duration-300 hover:shadow-xl md:col-span-7" href="{{ url('/resources') }}">
                    <div class="flex h-full flex-col justify-between">
                        <div>
                            <div class="mb-8 flex h-16 w-16 items-center justify-center rounded-xl bg-primary-fixed text-primary-container">
                                <span class="material-symbols-outlined text-4xl">menu_book</span>
                            </div>
                            <h3 class="mb-4 text-3xl font-bold font-headline text-primary">Complete Documentation</h3>
                            <p class="max-w-md leading-relaxed text-on-surface-variant">
                                Access our exhaustive library of technical guides, administrative handbooks, and developer-focused API references.
                            </p>
                        </div>
                        <div class="mt-12 flex items-center gap-2 font-bold text-primary transition-all group-hover:gap-4">
                            Browse Docs <span class="material-symbols-outlined">arrow_forward</span>
                        </div>
                    </div>
                </a>
                <div class="flex flex-col gap-6 md:col-span-5">
                    <a class="group flex-1 cursor-pointer rounded-xl border border-outline-variant/10 bg-surface-container-low p-8 transition-all duration-300 hover:shadow-lg" href="{{ url('/faq') }}">
                        <div class="flex items-start gap-6">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-secondary-container text-on-secondary-container">
                                <span class="material-symbols-outlined">quiz</span>
                            </div>
                            <div>
                                <h4 class="mb-2 text-xl font-bold font-headline text-primary">Frequently Asked Questions</h4>
                                <p class="text-sm text-on-surface-variant">Quick answers to common queries about setup, billing, and classroom tools.</p>
                            </div>
                        </div>
                    </a>
                    <div class="group flex-1 cursor-pointer rounded-xl border border-outline-variant/10 bg-tertiary-fixed p-8 transition-all duration-300 hover:shadow-lg">
                        <div class="flex items-start gap-6">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-on-tertiary text-tertiary-container">
                                <span class="material-symbols-outlined">video_library</span>
                            </div>
                            <div>
                                <h4 class="mb-2 text-xl font-bold font-headline text-tertiary-container">Video Tutorials</h4>
                                <p class="text-sm text-on-tertiary-fixed-variant">Visual walkthroughs led by our product specialists for immersive learning.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="bg-surface-container-low py-32">
            <div class="mx-auto grid max-w-screen-2xl grid-cols-1 gap-16 px-8 md:grid-cols-12">
                <div class="md:col-span-7">
                    <div class="mb-12">
                        <h2 class="mb-4 text-4xl font-extrabold font-headline tracking-tight text-primary">Still need assistance?</h2>
                        <p class="text-lg text-on-surface-variant">Our expert academic support team is available around the clock to ensure your institution runs smoothly.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="rounded-xl bg-surface-container-lowest p-8 shadow-sm">
                            <h4 class="mb-6 flex items-center gap-2 text-xl font-bold text-primary">
                                <span class="material-symbols-outlined">chat_bubble</span> Live Chat
                            </h4>
                            <p class="mb-8 text-sm leading-relaxed text-on-surface-variant">Average response time: &lt; 2 minutes. Best for immediate technical troubleshooting.</p>
                            <button class="w-full rounded-xl bg-primary py-4 font-bold text-white shadow-md transition-transform hover:-translate-y-[2px]">
                                Start Conversation
                            </button>
                        </div>
                        <div class="rounded-xl bg-surface-container-lowest p-8 shadow-sm">
                            <h4 class="mb-6 flex items-center gap-2 text-xl font-bold text-primary">
                                <span class="material-symbols-outlined">mail</span> Email Support
                            </h4>
                            <p class="mb-8 text-sm leading-relaxed text-on-surface-variant">Average response time: 4-6 hours. Best for detailed account or billing inquiries.</p>
                            <button class="w-full rounded-xl bg-surface-container-high py-4 font-bold text-on-surface transition-transform hover:-translate-y-[2px]">
                                Send a Ticket
                            </button>
                        </div>
                    </div>
                    <div class="mt-12 flex items-center gap-4 rounded-xl border border-white bg-white/40 p-6">
                        <div class="h-2 w-2 animate-pulse rounded-full bg-secondary"></div>
                        <span class="text-sm font-semibold text-secondary">System Status: All Services Operational</span>
                        <a class="ml-auto text-sm font-bold text-primary hover:underline" href="#">View Historical Data</a>
                    </div>
                </div>
                <div class="flex flex-col gap-8 md:col-span-5">
                    <div class="relative overflow-hidden rounded-xl bg-primary p-10 text-white shadow-2xl">
                        <div class="absolute top-0 right-0 -mr-16 -mt-16 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
                        <div class="relative z-10">
                            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1.5 text-xs font-bold uppercase tracking-widest backdrop-blur-md">
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">verified</span> Professional Badge
                            </div>
                            <h3 class="mb-4 text-3xl font-bold font-headline">24/7 Technical Assistance</h3>
                            <p class="mb-8 text-sm leading-relaxed text-on-primary-container">Dedicated support for premium institution partners. No bots, only human experts.</p>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10">
                                        <span class="material-symbols-outlined">call</span>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wider text-on-primary-container">Direct Hotline</div>
                                        <div class="text-lg font-bold">+1 (888) EDU-CORE</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10">
                                        <span class="material-symbols-outlined">location_on</span>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wider text-on-primary-container">Global Headquarters</div>
                                        <div class="text-sm font-medium leading-tight">452 Academic Plaza, Suite 200<br />Cambridge, MA 02138, USA</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-8">
                        <h4 class="mb-4 font-bold text-primary">Support Hours</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">Standard Support</span>
                                <span class="font-semibold">Mon–Fri, 9am–6pm EST</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-on-surface-variant">Critical Incidents</span>
                                <span class="font-semibold text-secondary">24/7/365</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="w-full bg-[#f2f3fd] px-8 py-12 dark:bg-[#191b22]">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-1 gap-12 md:grid-cols-4">
            <div class="flex flex-col gap-4">
                <div class="text-xl font-bold text-[#1A4175] font-headline">Educore</div>
                <p class="text-sm leading-relaxed text-[#191b22]/60 dark:text-[#faf8ff]/60">
                    Empowering the next generation of educators with high-performance digital tools.
                </p>
            </div>
            <div class="flex flex-col gap-4">
                <h4 class="text-sm font-bold uppercase tracking-widest text-[#1A4175]">Platform</h4>
                <div class="flex flex-col gap-2">
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/pricing') }}">Pricing</a>
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/resources') }}">Resources</a>
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/request-demo') }}">Security</a>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <h4 class="text-sm font-bold uppercase tracking-widest text-[#1A4175]">Support</h4>
                <div class="flex flex-col gap-2">
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/faq') }}">FAQ</a>
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/resources') }}">Documentation</a>
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="{{ url('/support') }}">Status</a>
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <h4 class="text-sm font-bold uppercase tracking-widest text-[#1A4175]">Legal</h4>
                <div class="flex flex-col gap-2">
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="#">Privacy Policy</a>
                    <a class="text-sm text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#faf8ff]/60" href="#">Terms of Service</a>
                </div>
            </div>
        </div>
        <div class="mx-auto mt-16 flex max-w-screen-2xl flex-col items-center justify-between gap-4 border-t border-[#1A4175]/10 pt-8 md:flex-row">
            <span class="text-xs text-[#191b22]/60 dark:text-[#faf8ff]/60">&copy; 2024 Educore Management Systems. All rights reserved.</span>
            <div class="flex gap-6">
                <a class="text-[#191b22]/60 hover:text-[#1A4175]" href="#"><span class="material-symbols-outlined text-lg">language</span></a>
                <a class="text-[#191b22]/60 hover:text-[#1A4175]" href="#"><span class="material-symbols-outlined text-lg">share</span></a>
            </div>
        </div>
    </footer>
</body>
</html>
