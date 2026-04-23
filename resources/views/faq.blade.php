<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Educore | Frequently Asked Questions</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container-low": "#f2f3fd",
                        "secondary-fixed": "#9ff6ab",
                        "on-primary-fixed": "#001b3d",
                        "inverse-surface": "#2e3038",
                        "on-surface-variant": "#43474f",
                        "on-tertiary": "#ffffff",
                        "secondary-container": "#9ff6ab",
                        "on-tertiary-fixed": "#2e1500",
                        "secondary-fixed-dim": "#84d991",
                        "secondary": "#116d32",
                        "inverse-on-surface": "#f0f0fa",
                        "tertiary-fixed": "#ffdcc2",
                        "on-error-container": "#93000a",
                        "background": "#faf8ff",
                        "error": "#ba1a1a",
                        "on-primary-container": "#8caee9",
                        "tertiary-container": "#653500",
                        "on-primary-fixed-variant": "#21477b",
                        "surface-tint": "#3c5f95",
                        "surface": "#faf8ff",
                        "surface-container-highest": "#e1e2ec",
                        "on-tertiary-container": "#e49e62",
                        "on-secondary-container": "#1b7337",
                        "surface-dim": "#d9d9e3",
                        "primary-fixed-dim": "#a9c7ff",
                        "surface-container-high": "#e7e7f1",
                        "primary-fixed": "#d6e3ff",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#e1e2ec",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "on-background": "#191b22",
                        "on-primary": "#ffffff",
                        "on-secondary-fixed": "#002109",
                        "on-secondary-fixed-variant": "#005322",
                        "error-container": "#ffdad6",
                        "primary": "#002b59",
                        "on-surface": "#191b22",
                        "outline-variant": "#c3c6d1",
                        "inverse-primary": "#a9c7ff",
                        "surface-container": "#ededf7",
                        "tertiary-fixed-dim": "#ffb77b",
                        "surface-bright": "#faf8ff",
                        "outline": "#737780",
                        "surface-container-lowest": "#ffffff",
                        "tertiary": "#452200",
                        "primary-container": "#1a4175",
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

        .academic-grid {
            background-image: linear-gradient(to right, #e1e2ec 1px, transparent 1px), linear-gradient(to bottom, #e1e2ec 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(circle at 50% 50%, black, transparent 80%);
        }
    </style>
</head>
<body class="bg-background font-body text-on-surface selection:bg-primary-fixed selection:text-on-primary-fixed">
    <header class="fixed top-0 z-50 w-full bg-slate-50/80 shadow-sm backdrop-blur-lg dark:bg-slate-950/80 dark:shadow-none">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <nav class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/pricing') }}">Pricing</a>
                <a class="border-b-2 border-blue-700 pb-1 text-blue-700 dark:border-blue-300 dark:text-blue-300" href="{{ url('/faq') }}">FAQ</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/resources') }}">Resources</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/support') }}">Support</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/login') }}">Login</a>
                <a class="rounded-xl bg-primary px-5 py-2 text-sm font-bold tracking-wide text-on-primary shadow-md transition-transform hover:-translate-y-0.5 active:scale-95" href="{{ url('/register') }}">Get Started</a>
            </nav>
        </div>
        <div class="h-px bg-slate-200/20 dark:bg-slate-800/20"></div>
    </header>

    <main class="relative overflow-hidden pb-24 pt-32">
        <div class="academic-grid pointer-events-none absolute inset-0 opacity-20"></div>
        <div class="absolute right-0 top-0 -mr-24 -mt-24 h-96 w-96 rounded-full bg-primary-container/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-24 -ml-24 h-96 w-96 rounded-full bg-secondary-container/10 blur-3xl"></div>

        <div class="relative z-10 mx-auto max-w-4xl px-6">
            <div class="mb-16 text-center md:text-left">
                <h1 class="mb-6 font-headline text-5xl font-extrabold leading-none tracking-tighter text-primary md:text-6xl">
                    How can we help?
                </h1>
                <p class="max-w-2xl text-lg leading-relaxed text-on-surface-variant">
                    Find comprehensive answers to common inquiries about our pedagogical precision tools and administrative platform.
                </p>
            </div>

            <div class="group mb-16">
                <div class="relative flex items-center rounded-full border border-outline-variant/10 bg-surface-container-lowest p-2 shadow-sm transition-all focus-within:-translate-y-1 focus-within:shadow-lg">
                    <div class="flex items-center px-4 pl-6 text-outline">
                        <span class="material-symbols-outlined">search</span>
                    </div>
                    <input class="w-full border-none bg-transparent py-4 font-medium text-on-surface placeholder:text-outline focus:ring-0" placeholder="Search for questions, keywords, or topics..." type="text" />
                    <button class="mr-1 rounded-full bg-primary px-8 py-3 text-sm font-bold tracking-wide text-on-primary shadow-sm">
                        Search
                    </button>
                </div>
            </div>

            <div class="space-y-20">
                <section>
                    <div class="mb-8 flex items-center gap-4">
                        <div class="h-10 w-1 rounded-full bg-primary"></div>
                        <h2 class="font-headline text-2xl font-bold tracking-tight text-primary">General</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">What exactly is Educore Systems?</h3>
                                <span class="material-symbols-outlined text-primary">expand_more</span>
                            </div>
                            <div class="mt-4 font-body leading-relaxed text-on-surface-variant">
                                Educore is a next-generation school management ecosystem designed for precision in pedagogy. We provide administrators, teachers, and students with a unified cockpit for academic excellence.
                            </div>
                        </div>
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">Is Educore suitable for both K-12 and Higher Ed?</h3>
                                <span class="material-symbols-outlined text-primary">add</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-8 flex items-center gap-4">
                        <div class="h-10 w-1 rounded-full bg-secondary"></div>
                        <h2 class="font-headline text-2xl font-bold tracking-tight text-primary">Security</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">How is student data protected?</h3>
                                <span class="material-symbols-outlined text-primary">expand_more</span>
                            </div>
                            <div class="mt-4 font-body leading-relaxed text-on-surface-variant">
                                We employ military-grade encryption and comply with global standards including GDPR and FERPA. Your data is housed in isolated regional clusters with 24/7 monitoring.
                            </div>
                        </div>
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">Can we self-host the Educore database?</h3>
                                <span class="material-symbols-outlined text-primary">add</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-8 flex items-center gap-4">
                        <div class="h-10 w-1 rounded-full bg-tertiary-container"></div>
                        <h2 class="font-headline text-2xl font-bold tracking-tight text-primary">Implementation</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">How long does the onboarding process take?</h3>
                                <span class="material-symbols-outlined text-primary">add</span>
                            </div>
                        </div>
                        <div class="cursor-pointer rounded-xl border border-outline-variant/5 bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <h3 class="font-headline text-lg font-bold text-on-surface">What kind of training is provided for faculty?</h3>
                                <span class="material-symbols-outlined text-primary">expand_more</span>
                            </div>
                            <div class="mt-4 font-body leading-relaxed text-on-surface-variant">
                                We provide a tiered training program: asynchronous video modules for staff, live webinars for administrators, and 1-on-1 white-glove technical sessions for IT leads.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-32">
                    <div class="relative overflow-hidden rounded-[2rem] bg-primary-container p-12 text-center shadow-xl">
                        <div class="academic-grid pointer-events-none absolute inset-0 opacity-10"></div>
                        <div class="relative z-10">
                            <h2 class="mb-4 font-headline text-3xl font-extrabold tracking-tight text-white">Still have questions?</h2>
                            <p class="mx-auto mb-10 max-w-xl font-body text-lg text-on-primary-container">
                                Our dedicated support team is available 24/7 to help you optimize your institution's digital journey.
                            </p>
                            <div class="flex flex-wrap justify-center gap-4">
                                <a class="rounded-xl bg-white px-10 py-4 font-bold text-primary shadow-lg transition-colors hover:bg-slate-50 active:scale-95" href="{{ url('/support') }}">
                                    Contact Support
                                </a>
                                <button class="rounded-xl border border-white/30 bg-transparent px-10 py-4 font-bold text-white transition-colors hover:bg-white/10 active:scale-95">
                                    Live Chat
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer class="mt-auto w-full border-t border-slate-200 bg-slate-100 py-12 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-8 md:grid-cols-4">
            <div class="col-span-1 md:col-span-1">
                <div class="mb-4 font-['Manrope'] text-lg font-bold text-blue-950 dark:text-white">Educore</div>
                <p class="font-['Inter'] text-sm leading-relaxed text-slate-500 dark:text-slate-500">
                    Precision in Pedagogy. Empowering educational institutions through intelligent data management.
                </p>
            </div>
            <div class="flex flex-col gap-3">
                <h4 class="mb-2 font-headline text-sm font-bold text-primary">Navigation</h4>
                <a class="font-['Inter'] text-sm text-slate-500 transition-colors hover:text-blue-600 hover:underline decoration-2 underline-offset-4 dark:text-slate-500 dark:hover:text-blue-400" href="#">Privacy Policy</a>
                <a class="font-['Inter'] text-sm text-slate-500 transition-colors hover:text-blue-600 hover:underline decoration-2 underline-offset-4 dark:text-slate-500 dark:hover:text-blue-400" href="#">Terms of Service</a>
            </div>
            <div class="flex flex-col gap-3">
                <h4 class="mb-2 font-headline text-sm font-bold text-primary">Support</h4>
                <a class="font-['Inter'] text-sm text-slate-500 transition-colors hover:text-blue-600 hover:underline decoration-2 underline-offset-4 dark:text-slate-500 dark:hover:text-blue-400" href="#">Accessibility</a>
                <a class="font-['Inter'] text-sm text-slate-500 transition-colors hover:text-blue-600 hover:underline decoration-2 underline-offset-4 dark:text-slate-500 dark:hover:text-blue-400" href="{{ url('/support') }}">Contact Support</a>
            </div>
            <div class="flex flex-col gap-3">
                <h4 class="mb-2 font-headline text-sm font-bold text-primary">Status</h4>
                <a class="font-['Inter'] text-sm text-slate-500 transition-colors hover:text-blue-600 hover:underline decoration-2 underline-offset-4 dark:text-slate-500 dark:hover:text-blue-400" href="#">System Status</a>
            </div>
        </div>
        <div class="mx-auto mt-12 max-w-7xl border-t border-slate-200/50 px-8 pt-8">
            <p class="text-center font-['Inter'] text-sm leading-relaxed text-slate-400">&copy; 2024 Educore Systems. All rights reserved. Precision in Pedagogy.</p>
        </div>
    </footer>
</body>
</html>
