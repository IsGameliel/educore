<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Educore</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-fixed": "#d6e3ff",
                        "outline": "#737780",
                        "on-primary-container": "#8caee9",
                        "outline-variant": "#c3c6d1",
                        "on-secondary": "#ffffff",
                        "on-primary-fixed": "#001b3d",
                        "inverse-primary": "#a9c7ff",
                        "on-error": "#ffffff",
                        "on-error-container": "#93000a",
                        "error-container": "#ffdad6",
                        "primary-fixed-dim": "#a9c7ff",
                        "primary": "#002b59",
                        "on-tertiary": "#ffffff",
                        "secondary": "#116d32",
                        "on-tertiary-container": "#e49e62",
                        "tertiary": "#452200",
                        "surface-container-highest": "#e1e2ec",
                        "on-primary-fixed-variant": "#21477b",
                        "secondary-fixed-dim": "#84d991",
                        "surface-dim": "#d9d9e3",
                        "surface-container-low": "#f2f3fd",
                        "surface-container": "#ededf7",
                        "surface-container-high": "#e7e7f1",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "on-tertiary-fixed": "#2e1500",
                        "on-secondary-fixed-variant": "#005322",
                        "on-primary": "#ffffff",
                        "tertiary-fixed": "#ffdcc2",
                        "surface-tint": "#3c5f95",
                        "on-surface-variant": "#43474f",
                        "tertiary-container": "#653500",
                        "surface-container-lowest": "#ffffff",
                        "surface": "#faf8ff",
                        "secondary-fixed": "#9ff6ab",
                        "on-secondary-container": "#1b7337",
                        "inverse-on-surface": "#f0f0fa",
                        "error": "#ba1a1a",
                        "surface-variant": "#e1e2ec",
                        "primary-container": "#1a4175",
                        "inverse-surface": "#2e3038",
                        "on-secondary-fixed": "#002109",
                        "secondary-container": "#9ff6ab",
                        "on-background": "#191b22",
                        "surface-bright": "#faf8ff",
                        "on-surface": "#191b22",
                        "tertiary-fixed-dim": "#ffb77b",
                        "background": "#faf8ff"
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
    </style>
</head>
<body class="bg-surface text-on-surface">
    <header class="fixed left-0 top-0 z-50 w-full bg-[#faf8ff]/80 backdrop-blur-xl">
        <nav class="mx-auto flex w-full max-w-screen-2xl items-center justify-between px-6 py-4 lg:px-12">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <div class="hidden items-center gap-8 md:flex">
                <a class="font-['Manrope'] text-sm font-bold tracking-tight text-[#191b22]/60 transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175]" href="{{ url('/pricing') }}">Pricing</a>
                <a class="font-['Manrope'] text-sm font-bold tracking-tight text-[#191b22]/60 transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175]" href="{{ url('/faq') }}">FAQ</a>
                <a class="font-['Manrope'] text-sm font-bold tracking-tight text-[#191b22]/60 transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175]" href="{{ url('/resources') }}">Resources</a>
                <a class="font-['Manrope'] text-sm font-bold tracking-tight text-[#191b22]/60 transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175]" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-4 lg:gap-6">
                <div class="hidden items-center rounded-full bg-[#f2f3fd]/50 px-4 py-2 lg:flex">
                    <span class="material-symbols-outlined mr-2 text-sm text-[#191b22]/40">search</span>
                    <span class="text-xs font-medium text-[#191b22]/40">Search the academy...</span>
                </div>
                <div class="flex items-center gap-3 lg:gap-4">
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175] transition-transform hover:scale-110">notifications</span>
                    <span class="material-symbols-outlined cursor-pointer text-[#1A4175] transition-transform hover:scale-110">account_circle</span>
                    <a class="rounded-lg bg-primary px-4 py-2.5 text-sm font-bold tracking-tight text-on-primary shadow-lg shadow-primary/10 transition-all hover:-translate-y-[2px] lg:px-5" href="{{ url('/register') }}">
                        Get Started
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="pt-24">
        <section class="relative mx-auto grid max-w-screen-2xl grid-cols-1 items-center gap-12 px-6 py-16 lg:grid-cols-12 lg:px-12 lg:py-20">
            <div class="z-10 lg:col-span-6">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-secondary-container px-3 py-1 text-xs font-bold text-on-secondary-container">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                    THE 2024 SCHOLAR STANDARD
                </div>
                <h1 class="mb-8 text-5xl font-black leading-[0.95] tracking-tighter text-primary sm:text-6xl lg:text-7xl">
                    The digital soul of <span class="text-secondary">academic excellence.</span>
                </h1>
                <p class="mb-10 max-w-xl text-lg leading-relaxed text-on-surface-variant sm:text-xl">
                    Educore provides an integrated ecosystem designed for high-performance institutions. We bridge the gap between administrative precision and educational fluidity.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a class="flex items-center gap-3 rounded-xl bg-primary px-8 py-4 text-lg font-bold text-on-primary transition-all hover:-translate-y-[2px]" href="{{ url('/request-demo') }}">
                        Request a Demo
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                    <a class="rounded-xl bg-surface-container-high px-8 py-4 text-lg font-bold text-on-surface transition-all hover:-translate-y-[2px]" href="{{ url('/pricing') }}">
                        View Pricing
                    </a>
                </div>
            </div>
            <div class="relative h-[420px] lg:col-span-6 lg:h-[600px]">
                <div class="absolute inset-0 rotate-2 overflow-hidden rounded-[2rem] bg-primary-fixed">
                    <img class="h-full w-full object-cover opacity-90 mix-blend-multiply" alt="Modern high-tech university library with students studying on ergonomic furniture under soft architectural lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC2wuh0wAGG3rO71Zk720x6nYbJX8iSBA6236cLaku37Q0EzmBRffTCWSwV_5-WHzgOIsrdh6q4nXfsv2TuArUGC7CJpqDntVqI-j5aD2D95Ftd8iC1tnois1pXqfx2bgPsxA1BNoO_RHErJGvGWKChhdDDXs0r9NJYhwupa82P_-DxOYHtOl0lW0LQJg2DagFuDOL7Cx5lYWRqO8AtR3y6r7cThwIUtHiEwc-fM93JXO1m7uyh0LSiKFP94OSPAcx45MO7Yr63NoGf" />
                </div>
                <div class="absolute -bottom-4 left-0 w-56 rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-xl sm:-bottom-8 sm:-left-8 sm:w-64">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Global Reach</span>
                        <span class="material-symbols-outlined text-secondary">public</span>
                    </div>
                    <div class="mb-1 text-3xl font-black text-primary">2.4M+</div>
                    <div class="text-xs text-on-surface-variant">Scholars worldwide using Educore platforms.</div>
                </div>
                <div class="absolute -right-2 -top-4 w-48 -rotate-3 rounded-2xl bg-primary-container p-6 text-on-primary shadow-xl sm:-right-4 sm:-top-8 sm:w-56">
                    <span class="material-symbols-outlined mb-2 text-3xl">analytics</span>
                    <div class="mb-2 text-sm font-bold opacity-80">Efficiency Rating</div>
                    <div class="text-4xl font-black">+42%</div>
                </div>
            </div>
        </section>

        <section class="bg-surface-container-low px-6 py-20 lg:px-12 lg:py-24">
            <div class="mx-auto max-w-screen-2xl">
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <h2 class="mb-4 text-4xl font-black tracking-tight text-primary">Mastery through Integration</h2>
                    <p class="text-on-surface-variant">Every module is built to talk to the next. No silos, no friction, just pure academic focus.</p>
                </div>
                <div class="grid h-auto grid-cols-1 gap-6 md:grid-cols-12 md:h-[700px]">
                    <div class="group relative overflow-hidden rounded-[2rem] bg-surface-container-lowest p-8 md:col-span-8 md:p-12">
                        <div class="relative z-10">
                            <h3 class="mb-4 text-3xl font-black text-primary">Dynamic Analytics Cockpit</h3>
                            <p class="max-w-md text-on-surface-variant">Real-time student progress tracking with predictive AI insights. Identify at-risk scholars before they fall behind.</p>
                        </div>
                        <div class="relative z-10 mt-12">
                            <button class="rounded-full bg-surface-container px-6 py-3 font-bold text-primary transition-colors hover:bg-primary hover:text-on-primary">
                                Launch Explorer
                            </button>
                        </div>
                        <div class="absolute bottom-0 right-0 h-2/3 w-2/3 translate-x-12 translate-y-12 opacity-20 transition-opacity group-hover:opacity-40">
                            <span class="material-symbols-outlined text-[220px] text-primary md:text-[300px]" style="font-variation-settings: 'FILL' 1;">monitoring</span>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between rounded-[2rem] bg-primary-container p-8 text-on-primary md:col-span-4 md:p-10">
                        <div>
                            <span class="material-symbols-outlined mb-6 text-4xl">diversity_3</span>
                            <h3 class="mb-4 text-2xl font-bold leading-tight">Collaborative Faculty Nexus</h3>
                            <p class="text-sm opacity-70">Synchronize lesson planning and grading across global campuses in a unified workspace.</p>
                        </div>
                        <div class="flex -space-x-3 pt-8">
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-primary-container bg-surface">
                                <img class="h-full w-full object-cover" alt="Professional educator portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuyr_wTOimPxHnqsvMBxTI-OSCXIjbxm4TvoHSQ_JfmKUSdlWqNiSuuMBk5Z1-6Ugfkc6kZyed-xPPKEh1WCvLugTiF1j3BGPnhvSwKzYAPni2xlvQ3sip1w4xJ1F_g0cM3eRls_KdhJxs6BxobJ4nd2MNlN2gSrNr_l0Pp9khUeHSogy5xC7l7iGBGh1DiyxBBwo_xi-tXNGlZCn1v4gAkFo-vLsrMzVcxv68xLsjhj2gtV6lqey8c0mwfRMLn73HhhSi5xZxZX0M" />
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-primary-container bg-surface">
                                <img class="h-full w-full object-cover" alt="Smiling university professor" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBfEgnM6iOTZURN5_sHKr4KEXDov57E0lihzk-tEc0N3kvX7Scqz_gNWTWTlJ6mdtOirLIMVtSycYLhf35LAPnNjNpVb6dn8s44lGUg4TMDVlcaCN4JsOpdspc2wKT2ax8xBTRqX5b0FZKZifqz2kR3WANjvvKKBKjL17odgKxRMQnCQfxUJPhr7NuNDjlYeHnrQAs4iEFkRA4xhojpJKyy4HHbw54yBOzQ6U-ijhMmRjiXHMoMNu3lnS5mHaDQ_NS8F-R6OEJ1-6tT" />
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-primary-container bg-surface">
                                <img class="h-full w-full object-cover" alt="Female academic researcher" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBzIJ6R5HYtTMjPVcgJGc9GE47ggUdgoYfKpuHfeKNvty-cDo_0Y-25otZGDL0LHQ6OTSce10zU89uyqSAl-H5qLizjjwBKaE0ghtIpaTcng9aGEJeDSHgiJmk7AuWVN5PM2EVEQW6fomw-Y0sluEbQOHwsF7H9Pr61HceMQsCyw5zbmKHCissTvRK-QzS14HuCzwil6lFqw95NnUl7mtxuFcM_PuBMDn6OMQWKGUuguwF7IeYEXIezV6IutCuXYUNOtgYtst3RO4Tw" />
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-primary-container bg-surface-container-high text-[10px] font-bold text-on-surface">+42</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-[2rem] bg-tertiary-container p-8 text-on-tertiary-container md:col-span-4">
                        <span class="material-symbols-outlined text-3xl">verified_user</span>
                        <div>
                            <h4 class="mb-1 font-bold">Academic Integrity</h4>
                            <p class="text-xs opacity-80">Blockchain-verified credentials and automated plagiarism detection.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-[2rem] bg-secondary-container p-8 text-on-secondary-container md:col-span-4">
                        <span class="material-symbols-outlined text-3xl">account_balance</span>
                        <div>
                            <h4 class="mb-1 font-bold">Smart FAQ</h4>
                            <p class="text-xs opacity-80">Streamline the enrollment funnel with predictive scoring and CRM integration.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 rounded-[2rem] bg-surface-container-highest p-8 text-primary md:col-span-4">
                        <span class="material-symbols-outlined text-3xl">school</span>
                        <div>
                            <h4 class="mb-1 font-bold">Global Support</h4>
                            <p class="text-xs text-on-surface-variant">24/7 technical assistance for institutions across every time zone.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto flex max-w-screen-xl flex-col items-center px-6 py-24 text-center lg:px-12 lg:py-32">
            <span class="material-symbols-outlined mb-8 text-6xl text-outline-variant/30">format_quote</span>
            <blockquote class="mb-12 text-3xl font-black italic leading-tight tracking-tight text-primary md:text-5xl">
                "Educore didn't just digitize our records; they transformed the way our faculty engages with academic data. It's the standard for the modern university."
            </blockquote>
            <div class="flex items-center gap-4 text-left">
                <div class="h-16 w-16 overflow-hidden rounded-full">
                    <img class="h-full w-full object-cover" alt="Portrait of a female university dean in formal academic attire" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCQCPJe_EYGTTcHxaBT-cG-9YwzZk9Ozx-UiVZJUQy6E7036uXQ3IiddpcLQ0gwOhGadO_j8tST6_sSOvL1DxUV-Tpa4CJ1KagYjpaRt9ZHqg_FV3cTgTyo-dzZ-6ITCRZ5DPB8WKMo0l4fU2orZuw2lX2HWMiaFSO8WBYm9DtfXcB3UZhvoEsslDeXicHMaiaISIsNVQNfnLmSiGY-r-x5x-4HMOW2qgKl9A_ZtY0W99oWGcuS-BUlkwcp7CKS9fH8VHmOpL6ZtNg2" />
                </div>
                <div>
                    <div class="font-black uppercase tracking-tighter text-primary">Dr. Elena Rostova</div>
                    <div class="text-sm text-on-surface-variant">Dean of Academic Affairs, Global Institute</div>
                </div>
            </div>
        </section>

        <section class="px-6 py-20 lg:px-12">
            <div class="relative mx-auto max-w-screen-2xl overflow-hidden rounded-[3rem] bg-primary">
                <div class="pointer-events-none absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
                <div class="relative z-10 flex flex-col items-start justify-between gap-10 px-8 py-16 md:px-16 lg:flex-row lg:items-center lg:px-24 lg:py-24">
                    <div class="max-w-2xl">
                        <h2 class="mb-6 text-4xl font-black text-on-primary md:text-5xl">Ready to elevate your institution?</h2>
                        <p class="text-lg text-on-primary/70">Join 500+ elite academies redefining the educational experience with Educore's fluid management system.</p>
                    </div>
                    <div class="flex w-full flex-col gap-4 sm:w-auto sm:flex-row">
                        <a class="rounded-xl bg-secondary-container px-10 py-5 text-lg font-black text-on-secondary-container shadow-xl transition-transform hover:scale-105" href="{{ url('/register') }}">
                            Get Started Now
                        </a>
                        <button class="rounded-xl border border-white/20 bg-white/10 px-10 py-5 text-lg font-black text-white backdrop-blur-md transition-all hover:bg-white/20">
                            Download Thesis
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="w-full rounded-none border-t border-[#c3c6d1]/15 bg-[#f2f3fd]">
        <div class="mx-auto flex w-full max-w-screen-2xl flex-col items-center justify-between gap-8 px-6 py-12 md:flex-row lg:px-12">
            <div class="flex flex-col gap-4">
                <div class="font-['Manrope'] text-xl font-black text-[#1A4175]">Educore</div>
                <div class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70">&copy; 2024 Educore Systems. The Modern Scholar Standard.</div>
            </div>
            <div class="flex flex-wrap justify-center gap-x-12 gap-y-4">
                <a class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70 underline underline-offset-4 transition-colors duration-300 hover:text-[#1A4175]" href="#">Privacy Policy</a>
                <a class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70 underline underline-offset-4 transition-colors duration-300 hover:text-[#1A4175]" href="#">Terms of Service</a>
                <a class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70 underline underline-offset-4 transition-colors duration-300 hover:text-[#1A4175]" href="#">Academic Integrity</a>
                <a class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70 underline underline-offset-4 transition-colors duration-300 hover:text-[#1A4175]" href="#">Global Support</a>
                <a class="font-['Inter'] text-sm tracking-wide text-[#191b22]/70 underline underline-offset-4 transition-colors duration-300 hover:text-[#1A4175]" href="#">Documentation</a>
            </div>
            <div class="flex gap-4">
                <span class="material-symbols-outlined cursor-pointer text-[#1A4175] transition-transform hover:scale-110">language</span>
                <span class="material-symbols-outlined cursor-pointer text-[#1A4175] transition-transform hover:scale-110">share</span>
            </div>
        </div>
    </footer>
</body>
</html>
