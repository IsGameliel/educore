<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="description" content="Request an Euvion demonstration for your institution. Explore student records, course registration, results, departmental broadsheets and tuition management." />
    <title>Request an Euvion Demo | Academic Administration for Your School</title>
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
                <span class="material-symbols-outlined text-primary">auto_stories</span> Euvion
            </a>
            <nav class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/pricing') }}">Pricing</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/faq') }}">FAQ</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/resources') }}">Resources</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/support') }}">Support</a>
            </nav>
            <div class="flex items-center gap-4">
                <a class="text-sm font-semibold text-primary transition-colors hover:text-primary-container" href="{{ url('/login') }}">Login</a>
                <a class="rounded-xl bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-[2px] active:scale-95" href="#demo-form">Request Demo</a>
            </div>
        </div>
    </header>

    <main class="pt-24" id="main-content">
        <section class="mx-auto grid max-w-[1280px] items-start gap-10 px-6 py-14 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div>
                <p class="mb-4 text-xs font-bold uppercase tracking-widest text-secondary">Euvion for your institution</p>
                <h1 class="font-headline text-4xl font-extrabold leading-tight tracking-tight text-primary md:text-6xl">See your campus workflows come together.</h1>
                <p class="mt-6 text-lg leading-relaxed text-on-surface-variant">Explore how Euvion connects student records, course registration, results and tuition payments. Tell us what your institution needs so we can focus the demonstration on the work your team does every day.</p>
                <div class="mt-9 space-y-5">
                    @foreach([
                        ['how_to_reg', 'From student records to course registration', 'Review sessions, levels, course selections and registration controls.'],
                        ['fact_check', 'From draft marks to published results', 'Follow result entry, review and publication, then explore departmental broadsheets.'],
                        ['payments', 'From tuition invoices to payment records', 'See fee schedules, invoices, payment status and receipts in context.'],
                    ] as [$icon, $title, $description])
                    <div class="flex gap-4"><span class="material-symbols-outlined h-fit rounded-xl bg-surface-container-low p-3 text-primary" aria-hidden="true">{{ $icon }}</span><div><h2 class="font-headline font-bold text-primary">{{ $title }}</h2><p class="mt-1 text-sm leading-relaxed text-on-surface-variant">{{ $description }}</p></div></div>
                    @endforeach
                </div>
                <div class="mt-10 rounded-2xl bg-primary p-7 text-white">
                    <h2 class="font-headline text-xl font-bold">A useful conversation for your whole team</h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/80">Bring your registry, examination, finance or IT questions. We can discuss academic workflows, school setup and which available features fit your institution.</p>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold"><a href="{{ url('/pricing') }}" class="underline underline-offset-4">Explore plans</a><a href="{{ url('/resources') }}" class="underline underline-offset-4">Read Euvion guides</a></div>
                </div>
                <p class="mt-6 text-sm text-on-surface-variant">Already using Euvion and need help? <a href="{{ url('/support') }}" class="font-semibold text-primary underline underline-offset-4">Visit the support centre.</a></p>
            </div>

            <div id="demo-form" class="scroll-mt-28 rounded-2xl border border-outline-variant/50 bg-white p-6 shadow-lg md:p-9">
                @if(session('demo_sent'))
                    <div role="status" aria-live="polite">
                        <span class="material-symbols-outlined mb-5 rounded-xl bg-secondary-container p-3 text-secondary" aria-hidden="true">check_circle</span>
                        <h2 class="font-headline text-2xl font-bold text-primary">Your demo request has been sent.</h2>
                        <p class="mt-4 leading-relaxed text-on-surface-variant">Your details have been sent to the Euvion team at {{ config('euvion.demo_email') }}. We will use the email you provided to discuss your needs and arrange a suitable time.</p>
                        <p class="mt-4 text-sm text-on-surface-variant">This is a request for a demonstration. A meeting time has not yet been confirmed.</p>
                        <div class="mt-6 flex flex-wrap gap-4"><a href="{{ url('/resources') }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-white">Explore Euvion guides</a><a href="{{ route('demo.show') }}" class="self-center font-semibold text-primary underline underline-offset-4">Send another request</a></div>
                    </div>
                @else
                    <h2 class="font-headline text-2xl font-bold text-primary">Request your Euvion demo</h2>
                    <p class="mb-7 mt-3 text-sm leading-relaxed text-on-surface-variant">Share a few details about your institution. Fields marked with an asterisk (*) are required.</p>
                    @if($errors->any())
                    <div role="alert" class="mb-6 rounded-xl border border-error bg-error-container p-4 text-sm text-on-error-container"><p class="font-semibold">Please check your request:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form action="{{ route('demo.store') }}" method="POST" class="space-y-5">
                        @csrf
                        @foreach(['name' => ['Full name', 'Your full name', 'name', 120], 'email' => ['Email address', 'you@institution.edu', 'email', 254], 'institution' => ['Institution name', 'Your school or institution', 'organization', 200]] as $field => [$label, $placeholder, $autocomplete, $length])
                        <div><label for="demo-{{ $field }}" class="mb-2 block text-sm font-semibold text-primary">{{ $label }} *</label><input id="demo-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" autocomplete="{{ $autocomplete }}" value="{{ old($field) }}" placeholder="{{ $placeholder }}" required maxlength="{{ $length }}" @error($field) aria-invalid="true" @enderror class="w-full rounded-lg border border-outline-variant bg-white px-4 py-3 text-on-surface focus:border-primary focus:ring-primary" /></div>
                        @endforeach
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="demo-role" class="mb-2 block text-sm font-semibold text-primary">Your role *</label><select id="demo-role" name="role" required @error('role') aria-invalid="true" @enderror class="w-full rounded-lg border border-outline-variant bg-white px-4 py-3 focus:border-primary focus:ring-primary"><option value="">Select your role</option>@foreach(['Administrator', 'Registrar', 'Exam officer', 'Finance officer', 'Department head', 'IT officer', 'Lecturer', 'Other'] as $role)<option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>@endforeach</select></div>
                            <div><label for="demo-size" class="mb-2 block text-sm font-semibold text-primary">Student population</label><select id="demo-size" name="student_count" class="w-full rounded-lg border border-outline-variant bg-white px-4 py-3 focus:border-primary focus:ring-primary"><option value="">Select a range (optional)</option>@foreach(['Under 500', '500-1999', '2000-4999', '5000+', 'Not sure yet'] as $size)<option value="{{ $size }}" @selected(old('student_count') === $size)>{{ $size }}</option>@endforeach</select></div>
                        </div>
                        <fieldset><legend class="mb-3 text-sm font-semibold text-primary">What would you like to explore? <span class="font-normal text-on-surface-variant">(optional)</span></legend><div class="grid gap-3 sm:grid-cols-2">@foreach($demoTopics as $key => $topic)<label class="flex items-start gap-2 text-sm leading-relaxed text-on-surface-variant"><input type="checkbox" name="topics[]" value="{{ $key }}" @checked(in_array($key, (array) old('topics', []))) class="mt-1 rounded border-outline-variant text-primary focus:ring-primary" /><span>{{ $topic }}</span></label>@endforeach</div></fieldset>
                        <div><label for="demo-message" class="mb-2 block text-sm font-semibold text-primary">What would make this demo useful?</label><textarea id="demo-message" name="message" maxlength="2000" rows="4" placeholder="Tell us about your current process, priorities or questions. Please leave out student records and payment details." class="w-full rounded-lg border border-outline-variant bg-white px-4 py-3 focus:border-primary focus:ring-primary">{{ old('message') }}</textarea></div>
                        <label for="demo-consent" class="flex items-start gap-3 text-sm leading-relaxed text-on-surface-variant"><input id="demo-consent" type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1 rounded border-outline-variant text-primary focus:ring-primary" /><span>I agree that Euvion may use these details to contact me about this demo request. *</span></label>
                        <button class="gradient-hero w-full rounded-xl px-6 py-4 font-headline font-bold text-white transition-colors hover:opacity-90" type="submit">Send demo request</button>
                        <p class="text-center text-xs leading-relaxed text-on-surface-variant">This sends an enquiry to Euvion; it does not create a school account, book a meeting or start a subscription.</p>
                    </form>
                @endif
                <p class="mt-7 border-t border-outline-variant/50 pt-5 text-sm text-on-surface-variant">Prefer email? <a href="mailto:{{ config('euvion.demo_email') }}" class="break-all font-semibold text-primary underline underline-offset-4">{{ config('euvion.demo_email') }}</a></p>
            </div>
        </section>

        <section class="bg-surface-container-low py-16">
            <div class="mx-auto max-w-[1280px] px-6 lg:px-8">
                <h2 class="font-headline text-3xl font-bold tracking-tight text-primary">What happens after you enquire?</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach([
                        ['01', 'Share your priorities', 'Tell us who you are, the institution you represent and the workflows you would like to improve.'],
                        ['02', 'Plan the walkthrough', 'The Euvion team can follow up by email to clarify your questions and agree on a suitable demo time.'],
                        ['03', 'Explore your next steps', 'Discuss relevant features, plan options and the school information needed to prepare for onboarding.'],
                    ] as [$number, $title, $description])
                    <article class="rounded-xl border border-outline-variant/50 bg-white p-7"><span class="text-sm font-bold text-secondary">{{ $number }}</span><h3 class="mb-3 mt-4 font-headline text-xl font-bold text-primary">{{ $title }}</h3><p class="text-sm leading-relaxed text-on-surface-variant">{{ $description }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="mx-auto max-w-[1280px] px-6 py-14 lg:px-8">
            <div class="grid gap-8 md:grid-cols-2">
                <div><h2 class="font-headline text-2xl font-bold text-primary">Built around academic administration</h2><p class="mt-4 leading-relaxed text-on-surface-variant">Explore student and staff records, session and course setup, registration controls, result workflows, broadsheet exports, transcript requests and tuition management. Available modules depend on your institution's plan and configuration.</p></div>
                <div class="space-y-3"><details class="rounded-xl border border-outline-variant/50 bg-white p-5"><summary class="cursor-pointer font-semibold text-primary">Who should attend a demonstration?</summary><p class="mt-3 text-sm leading-relaxed text-on-surface-variant">Invite the staff who handle the workflows you want to explore: registry, examinations, finance, department administrators or IT.</p></details><details class="rounded-xl border border-outline-variant/50 bg-white p-5"><summary class="cursor-pointer font-semibold text-primary">Do I need to share student data?</summary><p class="mt-3 text-sm leading-relaxed text-on-surface-variant">You can describe your processes and questions without sending student records, payment information or passwords. This enquiry only needs your institution and contact details.</p></details></div>
            </div>
        </section>
    </main>
    <footer class="bg-surface-container-low py-10">
        <div class="mx-auto flex max-w-[1280px] flex-col justify-between gap-6 px-6 md:flex-row lg:px-8"><div><a href="{{ url('/') }}" class="text-lg font-bold text-primary">Euvion</a><p class="mt-2 text-sm text-on-surface-variant">Academic administration, connected.</p><p class="mt-3 text-xs text-on-surface-variant">&copy; {{ now()->year }} Euvion. All rights reserved.</p></div><nav aria-label="Footer" class="flex flex-wrap items-start gap-6 text-sm font-semibold text-primary"><a href="{{ url('/pricing') }}">Pricing</a><a href="{{ url('/resources') }}">Resources</a><a href="{{ url('/support') }}">Support</a><a href="{{ url('/faq') }}">FAQ</a></nav></div>
    </footer>
</body>
</html>
