<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Resources | Modern Scholar Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "secondary-container": "#9ff6ab",
                        "surface-dim": "#d9d9e3",
                        "on-primary-container": "#8caee9",
                        "tertiary": "#452200",
                        "tertiary-fixed": "#ffdcc2",
                        "on-error-container": "#93000a",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#e1e2ec",
                        "on-error": "#ffffff",
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
                        "surface-container-high": "#e7e7f1",
                        "on-primary-fixed-variant": "#21477b",
                        "tertiary-fixed-dim": "#ffb77b",
                        "on-surface": "#191b22",
                        "on-secondary-fixed": "#002109",
                        "primary-fixed-dim": "#a9c7ff",
                        "surface-bright": "#faf8ff",
                        "primary-fixed": "#d6e3ff"
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

        body {
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3, .font-headline {
            font-family: 'Manrope', sans-serif;
        }

        .glass-header {
            backdrop-filter: blur(20px);
        }

        .cloud-shadow {
            box-shadow: 0 10px 30px rgba(25, 27, 34, 0.06);
        }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-surface text-on-surface">
    <header class="fixed top-0 z-50 w-full bg-[#faf8ff]/80 shadow-[0_10px_30px_rgba(25,27,34,0.06)] backdrop-blur-xl dark:bg-[#191b22]/80">
        <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-4 lg:px-8">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <nav class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/pricing') }}">Pricing</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/faq') }}">FAQ</a>
                <a class="cursor-pointer border-b-2 border-[#1A4175] pb-1 text-[#1A4175] transition-transform duration-200 hover:-translate-y-[2px] dark:text-white" href="{{ url('/resources') }}">Resources</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/support') }}">Support</a>
            </nav>
            <div class="flex items-center gap-4">
                <div class="mr-4 hidden items-center gap-2 sm:flex">
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175]">help_outline</span>
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175]">notifications</span>
                </div>
                <a class="text-sm font-semibold text-primary transition-colors hover:text-primary-container" href="{{ url('/login') }}">Sign In</a>
                <a class="rounded-xl bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-[2px] active:scale-95" href="{{ url('/register') }}">Get Started</a>
            </div>
        </div>
    </header>

    <main class="flex-grow pt-24">
        <section class="mx-auto max-w-[1440px] px-6 py-16 lg:px-8">
            <div class="flex max-w-4xl flex-col items-start gap-8">
                <h1 class="text-5xl font-extrabold leading-tight tracking-tighter text-primary md:text-6xl">
                    Knowledge Base <span class="text-primary/40">&amp;</span> Insights
                </h1>
                <p class="max-w-2xl text-lg leading-relaxed text-on-surface-variant">
                    Access our comprehensive library of research, guides, and technical mastery tools designed to elevate academic administration.
                </p>
                <div class="relative mt-4 w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center">
                        <span class="material-symbols-outlined text-outline">search</span>
                    </div>
                    <input class="w-full rounded-xl border-none bg-surface-container-highest py-5 pl-12 pr-6 text-on-surface transition-all focus:ring-2 focus:ring-primary/20" placeholder="Search guides, case studies, or webinars..." type="text" />
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button class="rounded-full bg-primary px-5 py-2 text-sm font-medium text-on-primary transition-all hover:-translate-y-[2px]">All Resources</button>
                    <button class="rounded-full bg-surface-container-high px-5 py-2 text-sm font-medium text-on-surface transition-all hover:-translate-y-[2px]">Case Studies</button>
                    <button class="rounded-full bg-surface-container-high px-5 py-2 text-sm font-medium text-on-surface transition-all hover:-translate-y-[2px]">Product Guides</button>
                    <button class="rounded-full bg-surface-container-high px-5 py-2 text-sm font-medium text-on-surface transition-all hover:-translate-y-[2px]">Webinars</button>
                    <button class="rounded-full bg-surface-container-high px-5 py-2 text-sm font-medium text-on-surface transition-all hover:-translate-y-[2px]">Whitepapers</button>
                </div>
            </div>
        </section>

        <section class="bg-surface-container-low py-24">
            <div class="mx-auto max-w-[1440px] px-6 lg:px-8">
                <div class="mb-12 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-widest text-primary">Global Excellence</span>
                        <h2 class="text-4xl font-bold tracking-tight text-primary">Success Stories</h2>
                    </div>
                    <button class="group flex items-center gap-2 font-semibold text-primary">
                        View all stories <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
                    </button>
                </div>
                <div class="grid grid-cols-12 gap-8">
                    <div class="group col-span-12 cursor-pointer lg:col-span-8">
                        <div class="relative h-[500px] overflow-hidden rounded-xl">
                            <img alt="Featured Case Study" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAxEBj-86kmNdOayx52sVxELEkrEgOd-Kdux7TL70KxzJfxwJZ-Q1e6iVWFYuHrorykLG5c3RXOm9bQ5HQbibpmzUwEPUQ6ccXtg1VEaGHgz7VUjmQwGbSkPAnrfSTFW5GLcEFH2eQPpFH_rSzZMGcepwHOfLLoyNeRofTzNjRq19_xU70rUxyNfUTqa6Jj5Zat5HnzQvPsb8XrmjXTMHA4h-z29zZz8QbLWEQWqrr9uinCKnuIUG5gE4IPf2jD-uxlryvRhGkCy30N" />
                            <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent"></div>
                            <div class="absolute bottom-0 p-8 text-white md:p-12">
                                <span class="mb-4 inline-block rounded bg-secondary-container px-3 py-1 text-xs font-bold uppercase tracking-widest text-on-secondary-fixed-variant">Case Study</span>
                                <h3 class="mb-4 text-3xl font-bold md:text-4xl">Stanford Global Scaled Remote Learning</h3>
                                <p class="mb-6 max-w-xl text-lg text-on-primary-container">How one of the world's leading institutions transitioned 12,000+ students to a hybrid ecosystem within weeks using Educore's unified dashboard.</p>
                                <button class="rounded-xl bg-white px-8 py-3 font-bold text-primary transition-colors hover:bg-primary-fixed">Read Full Study</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 flex flex-col gap-8 lg:col-span-4">
                        <div class="cloud-shadow flex flex-col gap-4 rounded-xl border-l-4 border-primary bg-surface-container-lowest p-8">
                            <span class="text-xs font-bold uppercase tracking-tighter text-tertiary-container">Research Lab</span>
                            <h4 class="text-xl font-bold leading-tight">Optimizing Neural Network Training Infrastructure for PhD Candidates</h4>
                            <a class="flex items-center gap-1 text-sm font-semibold text-primary-container" href="#">Download PDF <span class="material-symbols-outlined text-sm">download</span></a>
                        </div>
                        <div class="cloud-shadow flex flex-col gap-4 rounded-xl border-l-4 border-secondary bg-surface-container-lowest p-8">
                            <span class="text-xs font-bold uppercase tracking-tighter text-secondary">K-12 Success</span>
                            <h4 class="text-xl font-bold leading-tight">Bridging the Digital Divide: District-Wide Implementation in Rural Ohio</h4>
                            <a class="flex items-center gap-1 text-sm font-semibold text-primary-container" href="#">Read Story <span class="material-symbols-outlined text-sm">open_in_new</span></a>
                        </div>
                        <div class="cloud-shadow flex flex-col gap-4 rounded-xl border-l-4 border-on-tertiary-container bg-surface-container-lowest p-8">
                            <span class="text-xs font-bold uppercase tracking-tighter text-on-tertiary-container">Alumni Relations</span>
                            <h4 class="text-xl font-bold leading-tight">Increasing Endowment Engagement by 40% via Data Personalization</h4>
                            <a class="flex items-center gap-1 text-sm font-semibold text-primary-container" href="#">View Metrics <span class="material-symbols-outlined text-sm">trending_up</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-[1440px] px-6 py-24 lg:px-8">
            <div class="mb-16">
                <h2 class="mb-4 text-4xl font-bold tracking-tight text-primary">Technical Mastery</h2>
                <p class="max-w-xl text-on-surface-variant">Deep-dive technical documentation and configuration guides for high-level administrative users.</p>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="group cursor-pointer rounded-xl border border-outline-variant/20 bg-surface p-8 transition-all hover:border-primary/40 hover:bg-surface-container-lowest hover:shadow-lg">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-primary-fixed text-primary transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined">settings_suggest</span>
                    </div>
                    <h3 class="mb-2 text-lg font-bold">Initial Setup</h3>
                    <p class="mb-6 text-sm text-on-surface-variant">Step-by-step framework for deploying Educore across multiple campus nodes.</p>
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">12 Chapters</span>
                </div>
                <div class="group cursor-pointer rounded-xl border border-outline-variant/20 bg-surface p-8 transition-all hover:border-primary/40 hover:bg-surface-container-lowest hover:shadow-lg">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-secondary-fixed text-secondary transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined">analytics</span>
                    </div>
                    <h3 class="mb-2 text-lg font-bold">Data Analytics</h3>
                    <p class="mb-6 text-sm text-on-surface-variant">Advanced query building and predictive modeling for student performance.</p>
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">8 Modules</span>
                </div>
                <div class="group cursor-pointer rounded-xl border border-outline-variant/20 bg-surface p-8 transition-all hover:border-primary/40 hover:bg-surface-container-lowest hover:shadow-lg">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-tertiary-fixed text-tertiary transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined">shield_person</span>
                    </div>
                    <h3 class="mb-2 text-lg font-bold">Privacy &amp; Compliance</h3>
                    <p class="mb-6 text-sm text-on-surface-variant">Ensuring FERPA, GDPR, and localized data residency requirements are met.</p>
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">Legal Review</span>
                </div>
                <div class="group cursor-pointer rounded-xl border border-outline-variant/20 bg-surface p-8 transition-all hover:border-primary/40 hover:bg-surface-container-lowest hover:shadow-lg">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-lg bg-on-primary-container/20 text-[#1A4175] transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined">api</span>
                    </div>
                    <h3 class="mb-2 text-lg font-bold">API Integrations</h3>
                    <p class="mb-6 text-sm text-on-surface-variant">Documentation for syncing SIS, LMS, and financial systems via our REST API.</p>
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">Developer SDK</span>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-primary py-24 text-white">
            <div class="pointer-events-none absolute right-0 top-0 h-full w-1/3 opacity-10">
                <svg class="h-full w-full" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0,400 Q200,300 400,400 L400,0 L0,0 Z" fill="white"></path>
                </svg>
            </div>
            <div class="relative z-10 mx-auto max-w-[1440px] px-6 lg:px-8">
                <div class="mb-16 flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="mb-4 text-4xl font-bold">Interactive Webinars</h2>
                        <p class="max-w-lg text-on-primary-container">Join live sessions with academic leaders or catch up on our extensive on-demand library.</p>
                    </div>
                    <div class="flex gap-4">
                        <button class="rounded-lg bg-white/10 px-6 py-2 font-medium transition-all hover:bg-white/20">Browse On-Demand</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="flex flex-col overflow-hidden rounded-2xl bg-primary-container shadow-2xl md:flex-row">
                        <div class="relative h-48 md:h-auto md:w-1/3">
                            <img alt="Webinar Host" class="h-full w-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAeOvCJtETxZS9pEIF3z4zCQn1bz6K2Rb3-_Etzef4-ex_jFCYEYHroleIx2wovuxI4TSt_tJaFy21GjMSOT6Ur0L8A3AayaRV1RH5XFU2ZRehlkPqxw8F7w51j6WQAmvVZDbBnWeMc78b-Y9IZhPJMXcpK1RnsedaxKbtP5VkeqG30kEKk9Ri_MjWefStSfbZAiLWZgati-QR_ScNVgZ1BPPGEjUGQaEqFFNFRFr6TDKPuFZMguwydtMLEeg9V49JmpBej5PL8SSFi" />
                            <div class="absolute left-4 top-4 flex items-center gap-1 rounded bg-error px-2 py-1 text-[10px] font-bold uppercase">
                                <span class="h-2 w-2 rounded-full bg-white"></span> Live Oct 24
                            </div>
                        </div>
                        <div class="flex flex-col justify-center p-8 md:w-2/3">
                            <h3 class="mb-2 text-2xl font-bold">The Future of AI in Curriculum Planning</h3>
                            <p class="mb-6 text-sm italic text-on-primary-container">With Dr. Elena Vance, Head of Educational Technology at Oxford</p>
                            <button class="self-start rounded-xl bg-secondary-container px-6 py-3 font-bold text-on-secondary-fixed-variant transition-all hover:scale-105">Register Now</button>
                        </div>
                    </div>
                    <div class="flex flex-col overflow-hidden rounded-2xl bg-primary-container opacity-90 shadow-2xl md:flex-row">
                        <div class="relative h-48 md:h-auto md:w-1/3">
                            <img alt="Webinar Background" class="h-full w-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDaHa1JXjm6YQDrnSlB_4bk4cSVA7yXdkNSU98ULkCMmY819QSTrPs141Lt8eaem7OyIDyVkKhwULgPVktRnUH7p8dhscn94TdkoOGBHs1c4oN-_lRznFQhW6k9hejEoQA2_HhcitCG79SyB-d0Y2ZiMO3Jealt_S0ARlOVrfYJF75pa6MM_b0lr46haT49aKuGKyr4njq4BTV6rpCjs89nY3H-8l8f6jRpvgAT4pQy7mTSCwbgT3pq4um0juN20XNMYQmqVBU6vni3" />
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-6xl text-white/50">play_circle</span>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center p-8 md:w-2/3">
                            <h3 class="mb-2 text-2xl font-bold">Data Security for Multi-Campus Districts</h3>
                            <p class="mb-6 text-sm italic text-on-primary-container">Recorded Session: IT Governance Series</p>
                            <button class="self-start rounded-xl border border-white/20 bg-white/10 px-6 py-3 font-bold text-white transition-all hover:bg-white/20">Watch Recording</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-[1200px] px-6 py-24 lg:px-8">
            <div class="flex flex-col items-center gap-8 rounded-[2rem] bg-surface-container-low p-10 text-center shadow-sm md:p-16">
                <span class="material-symbols-outlined text-5xl text-primary">mail</span>
                <div class="max-w-2xl">
                    <h2 class="mb-4 text-4xl font-bold tracking-tight text-primary">Never miss an update</h2>
                    <p class="text-on-surface-variant">Join 5,000+ academic leaders who receive our monthly digest of educational insights, product updates, and technical breakthroughs.</p>
                </div>
                <form class="flex w-full max-w-lg flex-col gap-4 md:flex-row">
                    <input class="flex-grow rounded-xl border-none bg-white px-6 py-4 text-on-surface focus:ring-2 focus:ring-primary/20" placeholder="Your academic email" type="email" />
                    <button class="whitespace-nowrap rounded-xl bg-primary px-8 py-4 font-bold text-white shadow-md transition-all hover:-translate-y-[2px]">Subscribe Now</button>
                </form>
                <p class="text-xs text-on-surface-variant opacity-60">We value your privacy. Unsubscribe at any time.</p>
            </div>
        </section>
    </main>

    <footer class="mt-auto w-full bg-[#f2f3fd] py-12 dark:bg-[#12141a]">
        <div class="mx-auto flex max-w-[1440px] flex-col items-center justify-between gap-8 px-6 md:flex-row lg:px-12">
            <div class="flex flex-col gap-2">
                <div class="font-['Manrope'] text-lg font-bold text-[#1A4175]">Educore</div>
                <div class="font-['Inter'] text-sm tracking-normal text-[#191b22]/60 dark:text-[#f2f3fd]/60">&copy; 2024 Educore. Empowering Academic Authority.</div>
            </div>
            <nav class="flex flex-wrap gap-8 font-['Inter'] text-sm tracking-normal">
                <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#f2f3fd]/60 dark:hover:text-white" href="#">Privacy Policy</a>
                <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#f2f3fd]/60 dark:hover:text-white" href="#">Terms of Service</a>
                <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#f2f3fd]/60 dark:hover:text-white" href="#">Support Hub</a>
                <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175] dark:text-[#f2f3fd]/60 dark:hover:text-white" href="#">Contact Sales</a>
            </nav>
        </div>
    </footer>
</body>
</html>
