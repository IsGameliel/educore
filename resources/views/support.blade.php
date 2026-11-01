<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="description" content="Get help with Euvion account access, course registration, results, broadsheets and tuition payments. Find guides and the right support team for your school." />
    <title>Euvion Support | Help for Your School Portal</title>
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
                <span class="material-symbols-outlined text-primary">auto_stories</span> Euvion
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
    <main class="pt-24" id="main-content">
        <section class="mx-auto max-w-[1280px] px-6 pb-12 pt-14 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 text-xs font-bold uppercase tracking-widest text-secondary">Euvion support centre</p>
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-primary md:text-6xl">Help for your Euvion school portal.</h1>
                <p class="mt-6 text-lg leading-relaxed text-on-surface-variant">Find help with account access, course registration, academic records and tuition payments. Start with a guide or find the right team to resolve your issue.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/resources') }}" class="rounded-xl bg-primary px-6 py-3 font-semibold text-white hover:bg-primary-container">Browse Euvion guides</a>
                    <a href="#contact-help" class="rounded-xl border border-outline-variant px-6 py-3 font-semibold text-primary hover:bg-surface-container-low">Find the right support</a>
                </div>
            </div>
            <div class="mt-10 rounded-xl border border-outline-variant/50 bg-white p-5 text-sm leading-relaxed text-on-surface-variant">
                <strong class="text-primary">Cannot sign in?</strong> Confirm that you are using your institution's portal address and account email.
                <a href="{{ route('password.request') }}" class="font-semibold text-primary underline underline-offset-4">Reset your password</a>
                or ask your school administrator to check your account.
            </div>
        </section>

        <section class="bg-surface-container-low py-14" aria-labelledby="help-topics">
            <div class="mx-auto max-w-[1280px] px-6 lg:px-8">
                <h2 id="help-topics" class="text-3xl font-bold tracking-tight text-primary">What do you need help with?</h2>
                <p class="mb-8 mt-3 text-on-surface-variant">Choose a topic to open a step-by-step guide in the Euvion resource centre.</p>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        ['explore', 'Account access', 'Sign in to the correct school portal, verify your email and check your profile.', 'getting-started'],
                        ['how_to_reg', 'Course registration', 'Check your session, semester, level, course selection and credit limits.', 'registration'],
                        ['school', 'Results and transcripts', 'View published grades, submit an appeal or track a transcript request.', 'student-results'],
                        ['table_chart', 'Departmental broadsheets', 'Export scores, letter grades and grade points across available sessions.', 'broadsheets'],
                        ['payments', 'Tuition and payments', 'Review invoices, check payment status and find confirmed receipts.', 'payments'],
                        ['menu_book', 'Teaching and course materials', 'Find course files, class schedules and available tests.', 'teaching'],
                    ] as [$icon, $title, $description, $guide])
                    <a href="{{ url('/resources') }}#guide-{{ $guide }}" class="rounded-xl border border-outline-variant/50 bg-white p-6 transition-colors hover:border-primary">
                        <span class="material-symbols-outlined mb-4 rounded-lg bg-surface-container-low p-3 text-primary" aria-hidden="true">{{ $icon }}</span>
                        <h3 class="text-lg font-bold text-primary">{{ $title }}</h3>
                        <p class="mb-5 mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $description }}</p>
                        <span class="text-sm font-semibold text-primary">Open guide &rarr;</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="contact-help" class="mx-auto max-w-[1280px] scroll-mt-28 px-6 py-16 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-primary">Get help from the right team</h2>
            <p class="mb-8 mt-3 max-w-3xl text-on-surface-variant">Your institution manages its student records and academic decisions. For technical issues, your school administrator can coordinate with Euvion using the support channel provided during onboarding.</p>
            <div class="grid items-start gap-6 md:grid-cols-3">
                <article class="h-full rounded-xl border border-outline-variant/50 bg-white p-7">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary">Students</p>
                    <h3 class="text-xl font-bold text-primary">Your school or department</h3>
                    <p class="mt-3 text-sm leading-relaxed text-on-surface-variant">Contact your school administrator or department for an incorrect level, missing courses, account permissions, registration approval, missing marks or transcript decisions.</p>
                    <p class="mt-4 text-sm leading-relaxed text-on-surface-variant">Include your matric number, course code, academic session and semester, where relevant.</p>
                    @php
                        $schoolEmail = config('institution.branding.contact_email');
                        $schoolPhone = config('institution.branding.contact_phone');
                    @endphp
                    @if($schoolEmail && filter_var($schoolEmail, FILTER_VALIDATE_EMAIL))
                        <a href="mailto:{{ $schoolEmail }}" class="mt-5 block break-all font-semibold text-primary underline underline-offset-4">{{ $schoolEmail }}</a>
                    @endif
                    @if($schoolPhone)
                        <p class="mt-3 text-sm text-primary">School contact: {{ $schoolPhone }}</p>
                    @endif
                    @if(!$schoolEmail && !$schoolPhone)
                        <p class="mt-5 rounded-lg bg-surface-container-low p-3 text-sm text-on-surface-variant">Use the contact details supplied by your institution or visit its student services office.</p>
                    @endif
                </article>
                <article class="h-full rounded-xl border border-outline-variant/50 bg-white p-7">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary">Payments</p>
                    <h3 class="text-xl font-bold text-primary">Your school finance office</h3>
                    <p class="mt-3 text-sm leading-relaxed text-on-surface-variant">Contact finance about an incorrect invoice, fee allocation, outstanding balance or a payment that has not been confirmed in the portal.</p>
                    <p class="mt-4 text-sm leading-relaxed text-on-surface-variant">Share the transaction reference, amount, payment date and the status shown in Euvion.</p>
                    <a href="{{ url('/resources') }}#guide-payments" class="mt-5 inline-block font-semibold text-primary underline underline-offset-4">Read the payment guide</a>
                </article>
                <article class="h-full rounded-xl bg-primary p-7 text-white">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary-container">School administrators</p>
                    <h3 class="text-xl font-bold">Euvion technical support</h3>
                    <p class="mt-3 text-sm leading-relaxed text-white/80">For portal errors, unavailable features or unexpected behaviour, use the Euvion support contact shared with your institution during onboarding.</p>
                    <p class="mt-4 text-sm leading-relaxed text-white/80">Include the school portal address, affected role, time of the issue and the steps needed to reproduce it.</p>
                    <a href="#issue-details" class="mt-5 inline-block font-semibold text-white underline underline-offset-4">Prepare your issue details</a>
                </article>
            </div>
        </section>

        <section class="bg-surface-container-low py-16">
            <div class="mx-auto grid max-w-[1280px] items-start gap-10 px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-primary">Quick troubleshooting</h2>
                    <p class="mb-7 mt-3 text-on-surface-variant">A few checks for common Euvion questions.</p>
                    <div class="space-y-3">
                        @foreach([
                            ['Why can I not sign in?', 'Check the school portal address and the email registered to your account. Use password recovery if needed. If the account is inactive or your role is incorrect, contact your school administrator.'],
                            ['Why is a course or session missing?', 'Confirm the selected session, semester, department and level. Course availability also depends on your school registration settings and prerequisites. Ask your department to review incorrect or missing records.'],
                            ['Why can I not see my result?', 'Students can only view published results. Confirm the academic session and semester, then contact your department if the course is still missing.'],
                            ['How do I export previous-session broadsheets?', 'In Academic Reports, choose the department and latest session you need, then select Export all available sessions (Excel). Earlier available sessions through your selection are included. Leave the semester filter open to include both semesters.'],
                            ['Why does my payment still show pending?', 'Open the payment record and use the refresh option where available. If you were debited but the payment is still unresolved, provide the transaction reference, amount and date to your school finance office for verification.'],
                            ['Why can I not edit submitted results?', 'Submitted results are locked through the review workflow. An authorized officer must use the permitted return-to-draft or correction process. Published results should be changed through the academic correction workflow.'],
                        ] as [$question, $answer])
                        <details class="rounded-xl border border-outline-variant/50 bg-white p-5">
                            <summary class="cursor-pointer font-semibold text-primary">{{ $question }}</summary>
                            <p class="mt-4 text-sm leading-relaxed text-on-surface-variant">{{ $answer }}</p>
                        </details>
                        @endforeach
                    </div>
                </div>
                <aside id="issue-details" class="scroll-mt-28 rounded-2xl border border-outline-variant/50 bg-white p-7 md:p-9">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary">Before you contact support</p>
                    <h2 class="text-2xl font-bold text-primary">Help us understand the issue</h2>
                    <p class="mb-5 mt-3 text-sm leading-relaxed text-on-surface-variant">Prepare these details for your school support team or your institution's Euvion contact:</p>
                    <ol class="list-decimal space-y-3 pl-5 text-sm leading-relaxed text-on-surface-variant">
                        <li>Your institution name and school portal address.</li>
                        <li>Your role and the page or feature you were using.</li>
                        <li>What you tried to do, what you expected and what happened.</li>
                        <li>The exact error message, date and approximate time.</li>
                        <li>The relevant session, semester, course code or transaction reference.</li>
                        <li>A screenshot with unrelated personal information covered, if it helps explain the issue.</li>
                    </ol>
                    <p class="mt-6 rounded-lg bg-surface-container-low p-4 text-sm leading-relaxed text-on-surface-variant">Keep your password, card PIN and one-time verification codes private. Support teams do not need them to investigate a portal issue.</p>
                    <a href="{{ url('/resources') }}" class="mt-6 inline-block font-semibold text-primary underline underline-offset-4">Check the Euvion guides first &rarr;</a>
                </aside>
            </div>
        </section>
        <section class="mx-auto max-w-[1280px] px-6 py-14 lg:px-8">
            <div class="flex flex-col justify-between gap-6 rounded-xl border border-outline-variant/50 bg-white p-8 md:flex-row md:items-center">
                <div><h2 class="text-2xl font-bold text-primary">Exploring Euvion for your institution?</h2><p class="mt-2 text-on-surface-variant">Review the plans or request a demonstration of Euvion's academic workflows.</p></div>
                <div class="flex shrink-0 flex-wrap gap-3"><a href="{{ url('/pricing') }}" class="rounded-xl border border-outline-variant px-5 py-3 font-semibold text-primary">View plans</a><a href="{{ url('/request-demo') }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-white">Request a demo</a></div>
            </div>
        </section>
    </main>
    <footer class="bg-surface-container-low py-10">
        <div class="mx-auto flex max-w-[1280px] flex-col justify-between gap-6 px-6 md:flex-row lg:px-8">
            <div><a href="{{ url('/') }}" class="text-lg font-bold text-primary">Euvion</a><p class="mt-2 text-sm text-on-surface-variant">Academic administration, connected.</p><p class="mt-3 text-xs text-on-surface-variant">&copy; {{ now()->year }} Euvion. All rights reserved.</p></div>
            <nav aria-label="Footer" class="flex flex-wrap items-start gap-6 text-sm font-semibold text-primary"><a href="{{ url('/resources') }}">Resources</a><a href="{{ url('/faq') }}">FAQ</a><a href="{{ url('/pricing') }}">Pricing</a><a href="{{ url('/request-demo') }}">Request a demo</a></nav>
        </div>
    </footer>
</body>
</html>
