<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="description" content="Euvion guides for course registration, results, departmental broadsheets, tuition payments and school administration." />
    <title>Euvion Resources | Guides for Students &amp; School Teams</title>
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
                <span class="material-symbols-outlined text-primary">auto_stories</span> Euvion
            </a>
            <nav class="hidden items-center gap-8 font-['Manrope'] text-sm font-semibold tracking-tight md:flex">
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/pricing') }}">Pricing</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/faq') }}">FAQ</a>
                <a class="cursor-pointer border-b-2 border-[#1A4175] pb-1 text-[#1A4175] transition-transform duration-200 hover:-translate-y-[2px] dark:text-white" href="{{ url('/resources') }}">Resources</a>
                <a class="cursor-pointer text-[#191b22]/70 transition-colors transition-transform duration-200 hover:-translate-y-[2px] hover:text-[#1A4175] dark:text-[#f2f3fd]/70" href="{{ url('/support') }}">Support</a>
            </nav>
            <div class="flex items-center gap-4">
                <a class="text-sm font-semibold text-primary transition-colors hover:text-primary-container" href="{{ url('/login') }}">Sign In</a>
                <a class="rounded-xl bg-gradient-to-br from-primary to-primary-container px-6 py-2 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-[2px] active:scale-95" href="{{ url('/register') }}">Get Started</a>
            </div>
        </div>
    </header>

    <main class="flex-grow pt-24" id="main-content">
        <section class="mx-auto max-w-[1280px] px-6 pb-12 pt-14 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 text-xs font-bold uppercase tracking-widest text-secondary">The Euvion resource centre</p>
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-primary md:text-6xl">Make everyday campus work simpler.</h1>
                <p class="mt-6 text-lg leading-relaxed text-on-surface-variant">Practical guides for students, lecturers, exam officers and administrators using Euvion. Find your next step, from course registration to published results and tuition payments.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#guides" class="rounded-xl bg-primary px-6 py-3 font-semibold text-white hover:bg-primary-container">Explore the guides</a>
                    <a href="{{ url('/login') }}" class="rounded-xl border border-outline-variant px-6 py-3 font-semibold text-primary hover:bg-surface-container-low">Sign in to your portal</a>
                </div>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                @foreach([
                    ['01', 'New to Euvion?', 'Start with your school portal and the account provided by your institution.', '#guide-getting-started'],
                    ['02', 'Managing academic records?', 'Follow the result workflow and prepare departmental broadsheets.', '#guide-broadsheets'],
                    ['03', 'Need help with an issue?', 'Find the right information to share with your school or Euvion support.', '#getting-help'],
                ] as [$number, $title, $description, $link])
                <a href="{{ $link }}" class="rounded-xl border border-outline-variant/50 bg-white p-6 transition-colors hover:border-primary">
                    <span class="text-xs font-bold text-secondary">{{ $number }}</span>
                    <h2 class="mb-2 mt-3 text-lg font-bold text-primary">{{ $title }}</h2>
                    <p class="text-sm leading-relaxed text-on-surface-variant">{{ $description }}</p>
                </a>
                @endforeach
            </div>
        </section>

        @php
            $guides = [
                ['id' => 'getting-started', 'category' => 'Getting started', 'icon' => 'explore', 'audience' => 'Everyone', 'title' => 'Access your Euvion school portal',
                    'description' => 'Get into the right school portal and find the tools available to your role.',
                    'steps' => ['Open the portal address shared by your institution and sign in with your school account.', 'Complete email verification if prompted, then check your profile details and department.', 'Use the dashboard menu to access your available services. Menus depend on your role and the features enabled for your school.'],
                    'note' => 'If your account, role or school details are incorrect, contact your institution administrator.'],
                ['id' => 'registration', 'category' => 'Students', 'icon' => 'how_to_reg', 'audience' => 'Students and administrators', 'title' => 'Register courses for the right session',
                    'description' => 'Check your academic session, semester and level before submitting course registration.',
                    'steps' => ['Open course registration from your student dashboard and check the displayed session, semester and level.', 'Select the courses available to you. Check course units, prerequisites and the credit limit shown by the portal.', 'Submit your registration and review its status. Download your registration form where available.'],
                    'note' => 'For a missing course, incorrect level or historical registration, ask your department or school administrator to review the record.'],
                ['id' => 'student-results', 'category' => 'Students', 'icon' => 'school', 'audience' => 'Students', 'title' => 'View results, appeals and transcripts',
                    'description' => 'Find published grades and use the academic records services available to your account.',
                    'steps' => ['Open Results and select the session and semester you want to view. Only published results are visible to students.', 'If a published mark needs review, open Appeals and provide the course details and a clear explanation.', 'Open Transcripts to submit a request and track its decision. Follow any instructions provided by your institution.'],
                    'note' => 'Missing or unpublished results should be raised with your department. An appeal or transcript request is subject to institutional review.'],
                ['id' => 'result-workflow', 'category' => 'Academic records', 'icon' => 'fact_check', 'audience' => 'Lecturers, exam officers and administrators', 'title' => 'Enter, review and publish results',
                    'description' => 'Move marks from draft entry through review, approval and publication.',
                    'steps' => ['Use Enter Results or Upload Results for the appropriate course, session and semester. For bulk uploads, use the template supplied in Euvion.', 'Check student registrations, marks and validation messages before submitting the draft results.', 'Authorized exam officers or administrators review, approve and publish results through Review & Publish. Students see marks after publication.'],
                    'note' => 'Submitted marks are locked for editing. Use the permitted return-to-draft or correction workflow when a change is required.'],
                ['id' => 'broadsheets', 'category' => 'Academic records', 'icon' => 'table_chart', 'audience' => 'Exam officers and administrators', 'title' => 'Export departmental broadsheets',
                    'description' => 'Prepare scores, letter grades and grade points for one session or all available sessions.',
                    'steps' => ['Open Academic Reports and select a department and academic session. Choose a semester or leave it open for both semesters.', 'Choose Export broadsheet (Excel) for the selected session, or Export all available sessions (Excel) for earlier sessions through the selected session. A student in 200L can therefore have available 100L and 200L results in the same workbook.', 'Review the separate Scores, Letter Grade and Grade Point tables. Results are grouped by recorded level, session and semester, with student details, course units, TCC, TCE, TGP, GPA and carryovers.'],
                    'note' => 'The standard export includes reviewed, approved and published results. Review copies also include drafts. Unpublished marks are labelled; summaries and GPA use published results only.'],
                ['id' => 'payments', 'category' => 'Payments', 'icon' => 'payments', 'audience' => 'Students and finance staff', 'title' => 'Check tuition invoices and payment status',
                    'description' => 'Review the amount due, follow checkout and keep a record of confirmed payments.',
                    'steps' => ['Open My Payments or the tuition services available in your school portal. Review your invoice, fee breakdown and balance.', 'Use the payment option provided by your institution and follow the checkout instructions.', 'Return to Euvion to check the payment status. Where available, refresh a pending payment and download the receipt after confirmation.'],
                    'note' => 'For an unresolved payment, share the transaction reference, amount and date with your school finance office. Never share your password, card PIN or one-time code.'],
                ['id' => 'teaching', 'category' => 'Teaching', 'icon' => 'menu_book', 'audience' => 'Students and lecturers', 'title' => 'Find course materials, schedules and tests',
                    'description' => 'Keep teaching resources and scheduled academic activities in view.',
                    'steps' => ['Students can open course materials and filter for the course they need, then download available files.', 'Lecturers can use their authorized course-material tools to upload and manage files for their courses.', 'Check your class schedule and available tests in the dashboard. Follow the instructions and timing displayed for each activity.'],
                    'note' => 'If a material, class or test is missing, confirm the course and session with the lecturer or department. Access depends on your school setup.'],
                ['id' => 'administration', 'category' => 'Getting started', 'icon' => 'settings', 'audience' => 'School administrators', 'title' => 'Prepare your school for a new session',
                    'description' => 'Organize academic records and school settings before students begin registration.',
                    'steps' => ['Review faculties, departments, staff accounts, courses and lecturer assignments.', 'Check academic sessions, student levels, registration settings, credit limits and the grading and promotion policies applicable to your school.', 'Review tuition schedules with finance staff and confirm the payment integration where enabled. Use the backup or school export tools available to your institution before major record changes.'],
                    'note' => 'Confirm your school policies before changing session, registration or grading settings. Available modules depend on your institution plan and permissions.'],
            ];
        @endphp
        <section id="guides" class="scroll-mt-28 bg-surface-container-low py-16">
            <div class="mx-auto max-w-[1280px] px-6 lg:px-8">
                <div class="mb-8 max-w-2xl">
                    <h2 class="text-3xl font-bold tracking-tight text-primary">Find a guide for your task</h2>
                    <p class="mt-3 text-on-surface-variant">Open any guide for step-by-step instructions. Your school determines which features and services you can access.</p>
                </div>
                <div id="resource-controls" hidden>
                    <label for="resource-search" class="mb-2 block text-sm font-semibold text-primary">Search Euvion guides</label>
                    <input id="resource-search" type="search" placeholder="Try course registration, broadsheet or payment..." class="w-full rounded-xl border border-outline-variant bg-white px-5 py-4 text-on-surface focus:ring-2 focus:ring-primary" aria-controls="resource-guides" />
                    <div class="my-5 flex flex-wrap gap-2" role="group" aria-label="Filter guides by topic">
                        @foreach(['All guides', 'Getting started', 'Students', 'Academic records', 'Payments', 'Teaching'] as $category)
                        <button type="button" data-resource-filter="{{ $category }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="rounded-full border border-outline-variant px-4 py-2 text-sm font-semibold transition-colors {{ $loop->first ? 'bg-primary text-white' : 'bg-white text-primary' }}">{{ $category }}</button>
                        @endforeach
                    </div>
                    <p id="resource-count" class="mb-6 text-sm text-on-surface-variant" role="status" aria-live="polite" aria-atomic="true"></p>
                </div>
                <div id="resource-guides" class="grid items-start gap-6 md:grid-cols-2">
                    @foreach($guides as $guide)
                    <article id="guide-{{ $guide['id'] }}" data-resource-card data-category="{{ $guide['category'] }}" class="scroll-mt-28 rounded-xl border border-outline-variant/50 bg-white p-6 cloud-shadow md:p-8">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <span class="material-symbols-outlined rounded-xl bg-surface-container-low p-3 text-primary" aria-hidden="true">{{ $guide['icon'] }}</span>
                            <span class="text-xs font-bold uppercase tracking-wide text-secondary">{{ $guide['category'] }}</span>
                        </div>
                        <p class="mb-2 text-xs text-on-surface-variant">{{ $guide['audience'] }}</p>
                        <h3 class="text-xl font-bold leading-snug text-primary">{{ $guide['title'] }}</h3>
                        <p class="mb-5 mt-3 text-sm leading-relaxed text-on-surface-variant">{{ $guide['description'] }}</p>
                        <details class="border-t border-outline-variant/50 pt-4">
                            <summary class="cursor-pointer font-semibold text-primary">Read guide<span class="sr-only">: {{ $guide['title'] }}</span></summary>
                            <ol class="mt-5 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-on-surface-variant">
                                @foreach($guide['steps'] as $step)<li>{{ $step }}</li>@endforeach
                            </ol>
                            <p class="mt-5 rounded-lg bg-surface-container-low p-4 text-sm leading-relaxed text-on-surface-variant">{{ $guide['note'] }}</p>
                        </details>
                    </article>
                    @endforeach
                </div>
                <div id="resource-empty" hidden class="rounded-xl border border-outline-variant bg-white p-10 text-center">
                    <h3 class="text-xl font-bold text-primary">No guides match your search</h3>
                    <p class="mb-5 mt-2 text-on-surface-variant">Try a different keyword or show all topics.</p>
                    <button id="resource-reset" type="button" class="rounded-xl bg-primary px-6 py-3 font-semibold text-white">Show all guides</button>
                </div>
            </div>
        </section>
        <section id="getting-help" class="mx-auto max-w-[1280px] scroll-mt-28 px-6 py-16 lg:px-8">
            <div class="grid gap-10 rounded-2xl bg-primary p-8 text-white md:grid-cols-2 md:p-12">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-secondary-container">Help with the next step</p>
                    <h2 class="text-3xl font-bold">Need a hand with Euvion?</h2>
                    <p class="mt-4 leading-relaxed text-white/80">For account access, course registration, marks or tuition records, start with your school administrator, department or finance office. They manage your institutional records.</p>
                    <p class="mt-4 leading-relaxed text-white/80">For a technical issue, note the page, what you were trying to do and the error message. Include your school name when contacting support.</p>
                    <a href="{{ url('/support') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-primary">Visit Euvion support</a>
                </div>
                <div class="flex flex-col justify-center gap-4">
                    <a href="{{ url('/faq') }}" class="rounded-xl border border-white/20 p-5 hover:bg-white/10"><h3 class="font-bold">Frequently asked questions &rarr;</h3><p class="mt-1 text-sm text-white/80">Explore answers about the platform and getting started.</p></a>
                    <a href="{{ url('/pricing') }}" class="rounded-xl border border-white/20 p-5 hover:bg-white/10"><h3 class="font-bold">Explore Euvion plans &rarr;</h3><p class="mt-1 text-sm text-white/80">Review the features available for your institution.</p></a>
                    <a href="{{ url('/request-demo') }}" class="rounded-xl border border-white/20 p-5 hover:bg-white/10"><h3 class="font-bold">Request a demonstration &rarr;</h3><p class="mt-1 text-sm text-white/80">See how Euvion fits your school's academic workflows.</p></a>
                </div>
            </div>
        </section>
    </main>
    <footer class="mt-auto bg-surface-container-low py-10">
        <div class="mx-auto flex max-w-[1280px] flex-col justify-between gap-6 px-6 md:flex-row lg:px-8">
            <div><a href="{{ url('/') }}" class="text-lg font-bold text-primary">Euvion</a><p class="mt-2 text-sm text-on-surface-variant">Academic administration, connected.</p><p class="mt-3 text-xs text-on-surface-variant">&copy; {{ now()->year }} Euvion. All rights reserved.</p></div>
            <nav aria-label="Footer" class="flex flex-wrap items-start gap-6 text-sm font-semibold text-primary">
                <a href="{{ url('/pricing') }}">Pricing</a><a href="{{ url('/faq') }}">FAQ</a><a href="{{ url('/support') }}">Support</a><a href="{{ url('/request-demo') }}">Request a demo</a>
            </nav>
        </div>
    </footer>
    <script>
        (() => {
            const search = document.getElementById('resource-search');
            const cards = [...document.querySelectorAll('[data-resource-card]')];
            const filters = [...document.querySelectorAll('[data-resource-filter]')];
            let category = 'All guides';
            const update = () => {
                const terms = search.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                let count = 0;
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.hidden = !(category === 'All guides' || card.dataset.category === category) || !terms.every(term => text.includes(term));
                    if (!card.hidden) count++;
                });
                filters.forEach(button => {
                    const active = button.dataset.resourceFilter === category;
                    button.setAttribute('aria-pressed', String(active));
                    button.classList.toggle('bg-primary', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('bg-white', !active);
                    button.classList.toggle('text-primary', !active);
                });
                document.getElementById('resource-count').textContent = `${count} ${count === 1 ? 'guide' : 'guides'} found`;
                document.getElementById('resource-empty').hidden = count !== 0;
            };
            filters.forEach(button => button.addEventListener('click', () => { category = button.dataset.resourceFilter; update(); }));
            search.addEventListener('input', update);
            document.getElementById('resource-reset').addEventListener('click', () => { search.value = ''; category = 'All guides'; update(); search.focus(); });
            const followGuide = () => {
                const card = cards.find(card => `#${card.id}` === window.location.hash);
                if (!card) return;
                search.value = ''; category = 'All guides'; update();
                card.querySelector('details').open = true;
                card.scrollIntoView({ block: 'start' });
            };
            document.getElementById('resource-controls').hidden = false;
            window.addEventListener('hashchange', followGuide);
            update();
            followGuide();
        })();
    </script>
</body>
</html>
