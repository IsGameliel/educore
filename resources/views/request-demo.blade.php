<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Request a Demo | EduCore - The Modern Scholar</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-primary": "#ffffff",
                        "secondary": "#116d32",
                        "on-tertiary-container": "#e49e62",
                        "outline": "#737780",
                        "primary": "#002b59",
                        "on-tertiary": "#ffffff",
                        "surface-bright": "#faf8ff",
                        "error-container": "#ffdad6",
                        "surface-container-low": "#f2f3fd",
                        "on-surface-variant": "#43474f",
                        "inverse-surface": "#2e3038",
                        "surface-variant": "#e1e2ec",
                        "on-primary-fixed-variant": "#21477b",
                        "on-secondary-container": "#1b7337",
                        "secondary-container": "#9ff6ab",
                        "on-tertiary-fixed": "#2e1500",
                        "tertiary-container": "#653500",
                        "tertiary-fixed": "#ffdcc2",
                        "primary-fixed": "#d6e3ff",
                        "surface-container-high": "#e7e7f1",
                        "on-error-container": "#93000a",
                        "on-secondary-fixed": "#002109",
                        "tertiary-fixed-dim": "#ffb77b",
                        "inverse-primary": "#a9c7ff",
                        "primary-container": "#1a4175",
                        "on-error": "#ffffff",
                        "on-background": "#191b22",
                        "tertiary": "#452200",
                        "surface-tint": "#3c5f95",
                        "surface-dim": "#d9d9e3",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "on-primary-container": "#8caee9",
                        "on-primary-fixed": "#001b3d",
                        "inverse-on-surface": "#f0f0fa",
                        "surface-container-lowest": "#ffffff",
                        "secondary-fixed-dim": "#84d991",
                        "primary-fixed-dim": "#a9c7ff",
                        "surface-container": "#ededf7",
                        "outline-variant": "#c3c6d1",
                        "on-secondary-fixed-variant": "#005322",
                        "secondary-fixed": "#9ff6ab",
                        "on-secondary": "#ffffff",
                        "on-surface": "#191b22",
                        "error": "#ba1a1a",
                        "background": "#faf8ff",
                        "surface-container-highest": "#e1e2ec",
                        "surface": "#faf8ff"
                    },
                    borderRadius: {
                        DEFAULT: "0.125rem",
                        lg: "0.25rem",
                        xl: "0.5rem",
                        full: "0.75rem"
                    },
                    fontFamily: {
                        headline: ["Manrope", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                        label: ["Inter", "sans-serif"]
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .text-balance {
            text-wrap: balance;
        }

        .gradient-hero {
            background: linear-gradient(135deg, #002b59 0%, #1a4175 100%);
        }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased">
    <header class="fixed top-0 z-50 w-full bg-[#faf8ff]/80 shadow-[0_10px_30px_rgba(25,27,34,0.06)] backdrop-blur-xl dark:bg-[#191b22]/80">
        <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-4 lg:px-8">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <nav class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/pricing') }}">Pricing</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/faq') }}">FAQ</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/resources') }}">Resources</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/support') }}">Support</a>
            </nav>
            <div class="flex items-center gap-4">
                <div class="mr-4 hidden items-center gap-2 sm:flex">
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175]">help_outline</span>
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175]">notifications</span>
                </div>
                <a class="text-sm font-semibold text-primary transition-colors hover:text-primary-container" href="{{ url('/login') }}">Login</a>
                <a class="rounded-xl bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-[2px] active:scale-95" href="{{ url('/request-demo') }}">Request Demo</a>
            </div>
        </div>
    </header>

    <main class="pt-24">
        <section class="mx-auto grid max-w-7xl grid-cols-1 items-start gap-16 px-6 py-16 lg:grid-cols-2 lg:px-8">
            <div class="space-y-12">
                <div class="space-y-6">
                    <h1 class="text-balance font-headline text-5xl font-extrabold leading-tight tracking-tight text-primary md:text-6xl">
                        See the Future of Academic Management.
                    </h1>
                    <p class="max-w-lg text-xl leading-relaxed text-on-surface-variant">
                        Experience the platform designed for the modern scholar. Join elite institutions worldwide in transforming the educational journey.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 rounded-xl bg-surface-container-low p-8">
                        <span class="font-headline text-4xl font-bold text-secondary">98%</span>
                        <p class="text-sm font-medium uppercase tracking-widest text-on-surface-variant">Satisfaction rate</p>
                    </div>
                    <div class="space-y-2 rounded-xl bg-surface-container-low p-8">
                        <span class="font-headline text-4xl font-bold text-primary">24/7</span>
                        <p class="text-sm font-medium uppercase tracking-widest text-on-surface-variant">Expert Support</p>
                    </div>
                </div>

                <div class="group relative">
                    <div class="gradient-hero absolute inset-0 rotate-1 rounded-xl opacity-10 transition-transform duration-500 group-hover:rotate-0"></div>
                    <img alt="Educators in a meeting" class="relative z-10 h-[400px] w-full rounded-xl object-cover shadow-lg" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC0O8UepnLT0dLDRoF_3pVZe1xadIP9FEgb6HTcNZ7Z6uVmteCLU0XJKr6HFTOlpZBba5sOFAOWqwr2LGOQ_6nvCh8MLuds4KrIdqMcScENRvQ7zNy6fwV6_iRdTL6z4PyKynlqEOZooO0n_48_8CmL3FAZcyUNcl9iHbiyNSai0iwrBs_F1OV-1UtwSGRSI6WGj2oWaXlFRoQJRfq8ovHRStr0htY5n5DfvsFlgLY2V2aeA8QcYsKt2W1Sg55jToXy2PblIWu479l2" />
                    <div class="absolute -bottom-6 -right-6 z-20 flex items-center gap-4 rounded-xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-xl">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-container">
                            <span class="material-symbols-outlined text-on-secondary-container">school</span>
                        </div>
                        <div>
                            <p class="font-headline font-bold text-primary">Global Network</p>
                            <p class="text-sm text-on-surface-variant">Used by 500+ Top Schools</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky top-32">
                <div class="rounded-xl border border-outline-variant/15 bg-surface-container-lowest p-10 shadow-2xl">
                    <div class="mb-8">
                        <h2 class="mb-2 font-headline text-2xl font-bold text-primary">Schedule Your Strategy Session</h2>
                        <p class="text-on-surface-variant">Fill out the form below and an academic consultant will reach out within 4 hours.</p>
                    </div>
                    <form action="#" class="space-y-6">
                        <div class="space-y-1.5">
                            <label class="ml-1 font-label text-sm font-medium text-on-surface-variant">Full Name</label>
                            <input class="w-full rounded-lg border-none border-b-2 bg-surface-container-highest px-4 py-3.5 transition-all focus:border-primary focus:ring-0" placeholder="Dr. Julian Reed" type="text" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="ml-1 font-label text-sm font-medium text-on-surface-variant">Institutional Email</label>
                                <input class="w-full rounded-lg border-none border-b-2 bg-surface-container-highest px-4 py-3.5 transition-all focus:border-primary focus:ring-0" placeholder="j.reed@university.edu" type="email" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="ml-1 font-label text-sm font-medium text-on-surface-variant">School/Institution Name</label>
                                <input class="w-full rounded-lg border-none border-b-2 bg-surface-container-highest px-4 py-3.5 transition-all focus:border-primary focus:ring-0" placeholder="Global Academy" type="text" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 font-label text-sm font-medium text-on-surface-variant">Role</label>
                            <select class="w-full appearance-none rounded-lg border-none border-b-2 bg-surface-container-highest px-4 py-3.5 transition-all focus:border-primary focus:ring-0">
                                <option disabled selected value="">Select your position</option>
                                <option>Administrator</option>
                                <option>Department Head</option>
                                <option>IT Director</option>
                                <option>Registrar</option>
                                <option>Faculty Member</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 font-label text-sm font-medium text-on-surface-variant">Tell us about your needs</label>
                            <textarea class="w-full resize-none rounded-lg border-none border-b-2 bg-surface-container-highest px-4 py-3.5 transition-all focus:border-primary focus:ring-0" placeholder="Briefly describe your current challenges or institutional goals..." rows="4"></textarea>
                        </div>
                        <button class="gradient-hero w-full rounded-lg py-4 font-headline text-lg font-bold text-on-primary shadow-lg shadow-primary/20 transition-transform hover:-translate-y-1" type="submit">
                            Submit Request
                        </button>
                    </form>
                    <p class="mt-6 text-center text-xs text-on-surface-variant">
                        By submitting, you agree to our <a class="underline" href="#">Terms of Service</a> and <a class="underline" href="#">Privacy Policy</a>.
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-16 bg-surface-container-low px-6 py-24 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-16">
                <div class="mx-auto max-w-2xl space-y-4 text-center">
                    <h2 class="font-headline text-4xl font-extrabold tracking-tight text-primary">Empowering Every Aspect of Modern Education</h2>
                    <p class="text-on-surface-variant">The tools you need to foster excellence, from administrative efficiency to deep academic insights.</p>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="group rounded-xl bg-surface-container-lowest p-10 shadow-sm transition-shadow hover:shadow-md">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-lg bg-primary-container transition-transform duration-300 group-hover:-translate-y-2">
                            <span class="material-symbols-outlined text-3xl text-on-primary">view_module</span>
                        </div>
                        <h3 class="mb-4 font-headline text-xl font-bold text-primary">Modular Architecture</h3>
                        <p class="leading-relaxed text-on-surface-variant">
                            Scale your digital infrastructure with plug-and-play modules designed for specific institutional needs. No bloat, just performance.
                        </p>
                    </div>
                    <div class="group rounded-xl bg-surface-container-lowest p-10 shadow-sm transition-shadow hover:shadow-md">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-lg bg-secondary-container transition-transform duration-300 group-hover:-translate-y-2">
                            <span class="material-symbols-outlined text-3xl text-on-secondary-container">psychology</span>
                        </div>
                        <h3 class="mb-4 font-headline text-xl font-bold text-primary">AI-Driven Insights</h3>
                        <p class="leading-relaxed text-on-surface-variant">
                            Leverage predictive analytics to identify student trends, optimize resources, and improve learning outcomes with machine precision.
                        </p>
                    </div>
                    <div class="group rounded-xl bg-surface-container-lowest p-10 shadow-sm transition-shadow hover:shadow-md">
                        <div class="mb-8 flex h-14 w-14 items-center justify-center rounded-lg bg-tertiary-fixed transition-transform duration-300 group-hover:-translate-y-2">
                            <span class="material-symbols-outlined text-3xl text-on-tertiary-fixed-variant">verified_user</span>
                        </div>
                        <h3 class="mb-4 font-headline text-xl font-bold text-primary">Enterprise Security</h3>
                        <p class="leading-relaxed text-on-surface-variant">
                            Rest easy with FERPA and GDPR compliant data management. Military-grade encryption ensures institutional records remain private.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="mt-auto w-full border-t border-slate-200/50 bg-slate-50 px-6 py-12 dark:bg-slate-950 lg:px-8">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-8 md:grid-cols-2">
            <div class="space-y-4">
                <span class="font-headline text-xl font-bold text-slate-900 dark:text-white">Educore</span>
                <p class="font-body text-sm text-slate-500">&copy; 2024 EduCore Systems. All rights reserved.</p>
                <div class="mt-4 flex gap-6">
                    <a class="text-slate-400 transition-colors hover:text-blue-600" href="#"><span class="material-symbols-outlined">public</span></a>
                    <a class="text-slate-400 transition-colors hover:text-blue-600" href="#"><span class="material-symbols-outlined">alternate_email</span></a>
                    <a class="text-slate-400 transition-colors hover:text-blue-600" href="#"><span class="material-symbols-outlined">groups</span></a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-3">
                    <a class="block font-body text-sm text-slate-500 transition-all hover:text-blue-600" href="#">Privacy Policy</a>
                    <a class="block font-body text-sm text-slate-500 transition-all hover:text-blue-600" href="#">Terms of Service</a>
                </div>
                <div class="space-y-3">
                    <a class="block font-body text-sm text-slate-500 transition-all hover:text-blue-600" href="#">Security</a>
                    <a class="block font-body text-sm text-slate-500 transition-all hover:text-blue-600" href="#">Contact Support</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
