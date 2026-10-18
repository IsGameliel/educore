<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Pricing | Euvion Management Systems</title>
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
                <span class="material-symbols-outlined text-primary">auto_stories</span> Euvion
            </a>
            <div class="hidden items-center gap-8 font-headline text-sm font-semibold tracking-tight md:flex">
                <a class="border-b-2 border-blue-700 pb-1 text-blue-700 dark:border-blue-300 dark:text-blue-300" href="{{ url('/pricing') }}">Pricing</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/faq') }}">FAQ</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/resources') }}">Resources</a>
                <a class="text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="px-4 py-2 font-headline text-sm font-semibold text-slate-600 transition-colors hover:text-blue-900 dark:text-slate-400 dark:hover:text-white" href="{{ rtrim(config('tenancy.platform_url'), '/') }}/subscribe/login">Institution Login</a>
                <a class="rounded-lg bg-primary px-6 py-2.5 font-headline text-sm font-semibold text-on-primary shadow-lg shadow-primary/10 transition-transform hover:-translate-y-[2px]" href="#plans">Get Started</a>
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
                Choose Silver, Gold, or Platinum. Simple naira pricing per enrolled student per semester, with a promotional rate for your first semester.
            </p>
        </section>

        <section id="plans" class="mx-auto mb-20 max-w-screen-xl px-6 lg:px-12">
            <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-3">
                @foreach($plans as $plan)
                <article class="editorial-shadow flex h-full flex-col rounded-xl bg-white p-8 border border-slate-200 {{ $plan->code === 'gold' ? 'ring-2 ring-primary' : '' }}">
                    <h2 class="font-headline text-2xl font-extrabold text-primary uppercase">{{ $plan->name }}</h2>
                    <p class="mt-4 text-slate-500">Standard: <span class="line-through">₦{{ number_format($plan->standard_rate_minor / 100, 2) }}</span></p>
                    <p class="mt-2 text-4xl font-extrabold text-primary">₦{{ number_format($plan->promotional_rate_minor / 100, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-600">Promotional price per student per semester — first semester only.</p>
                    <p class="my-5 text-sm font-semibold">{{ ['silver'=>'Essential academic and administrative functionality', 'gold'=>'Everything in Silver, plus results, appeals and transcripts', 'platinum'=>'Everything in Gold, plus course materials, CBT and attendance'][$plan->code] ?? 'Academic and administrative functionality for your institution' }}</p>
                    <ul class="mb-8 flex-grow space-y-3">
                        @foreach($plan->features->where('implementation_status', '!=', 'not_found')->sortBy('name') as $feature)
                        <li class="flex gap-2 text-sm"><span class="text-secondary" aria-hidden="true">✓</span><span>{{ $feature->name }}@if($feature->implementation_status === 'not_found')<small class="block text-slate-500">Coming soon</small>@elseif($feature->implementation_status === 'partial')<small class="block text-slate-500">{{ $feature->notes }}</small>@endif</span></li>
                        @endforeach
                    </ul>
                    @if($plan->features->where('implementation_status','not_found')->isNotEmpty())
                    <details class="mb-6 text-sm text-slate-600"><summary>Deferred — excluded from this release</summary><ul class="mt-2">@foreach($plan->features->where('implementation_status','not_found') as $feature)<li>{{ $feature->name }}</li>@endforeach</ul><p>No delivery date is promised.</p></details>
                    @endif
                    @if($plan->signup_enabled)
                    <a class="block text-center rounded-xl bg-primary py-4 font-bold text-white" href="{{ rtrim(config('tenancy.platform_url'), '/') }}/subscribe/{{ $plan->code }}">Choose {{ $plan->name }}</a>
                    @else
                    <span class="block text-center rounded-xl bg-slate-100 py-4 font-bold text-slate-500">Signup currently closed</span>
                    @endif
                </article>
                @endforeach
            </div>
            <p class="mt-8 text-center text-slate-600">Billing uses enrolled students counted at semester start. Your first-semester invoice fixes the selected rate and student count.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-6">
                <a class="font-semibold text-primary underline" href="{{ rtrim(config('tenancy.platform_url'), '/') }}/subscribe/login">Already registered? Institution account login</a>
                <a class="font-semibold text-primary underline" href="{{ rtrim(config('tenancy.platform_url'), '/') }}/dashboard/login">Platform administrator login</a>
            </div>
        </section>
    </main>

    <footer class="mt-24 flex flex-col items-center justify-between gap-6 border-t border-slate-200 bg-slate-50 px-6 py-8 md:flex-row lg:px-12">
        <div>
            <div class="mb-2 font-headline text-lg font-bold text-blue-900">Euvion</div>
            <div class="font-body text-xs text-slate-500">&copy; 2024 Euvion Systems. All rights reserved.</div>
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
