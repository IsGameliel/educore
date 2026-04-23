<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Sign Up | Educore</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-primary-container": "#8caee9",
                        "on-tertiary-fixed-variant": "#6c3a04",
                        "secondary-container": "#9ff6ab",
                        "surface-dim": "#d9d9e3",
                        "on-error-container": "#93000a",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#e1e2ec",
                        "on-error": "#ffffff",
                        "tertiary": "#452200",
                        "tertiary-fixed": "#ffdcc2",
                        "on-surface-variant": "#43474f",
                        "surface-tint": "#3c5f95",
                        "surface": "#faf8ff",
                        "on-tertiary-container": "#e49e62",
                        "surface-container": "#ededf7",
                        "inverse-surface": "#2e3038",
                        "secondary-fixed": "#9ff6ab",
                        "outline-variant": "#c3c6d1",
                        "outline": "#737780",
                        "inverse-on-surface": "#f0f0fa",
                        "error-container": "#ffdad6",
                        "secondary": "#116d32",
                        "primary": "#002b59",
                        "primary-container": "#1a4175",
                        "on-primary-fixed": "#001b3d",
                        "on-background": "#191b22",
                        "on-primary": "#ffffff",
                        "on-secondary-container": "#1b7337",
                        "inverse-primary": "#a9c7ff",
                        "tertiary-container": "#653500",
                        "secondary-fixed-dim": "#84d991",
                        "error": "#ba1a1a",
                        "background": "#faf8ff",
                        "surface-container-low": "#f2f3fd",
                        "on-secondary-fixed-variant": "#005322",
                        "on-tertiary-fixed": "#2e1500",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-highest": "#e1e2ec",
                        "on-tertiary": "#ffffff",
                        "on-secondary-fixed": "#002109",
                        "surface-container-high": "#e7e7f1",
                        "on-primary-fixed-variant": "#21477b",
                        "tertiary-fixed-dim": "#ffb77b",
                        "on-surface": "#191b22",
                        "primary-fixed": "#d6e3ff",
                        "primary-fixed-dim": "#a9c7ff",
                        "surface-bright": "#faf8ff"
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
            vertical-align: middle;
        }

        [data-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3 {
            font-family: 'Manrope', sans-serif;
        }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-surface text-on-surface selection:bg-primary-fixed selection:text-on-primary-fixed">
    <nav class="fixed top-0 z-50 w-full bg-[#faf8ff]/80 backdrop-blur-xl">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 font-['Manrope'] tracking-tight lg:px-8">
            <a class="flex items-center gap-2 text-xl font-bold tracking-tighter text-[#002b59] dark:text-white" href="{{ url('/') }}">
                <span class="material-symbols-outlined text-primary">auto_stories</span> Educore
            </a>
            <div class="hidden items-center gap-12 md:flex">
                <a class="text-[#191b22]/70 transition-colors hover:text-[#1A4175]" href="{{ url('/pricing') }}">Pricing</a>
                <a class="text-[#191b22]/70 transition-colors hover:text-[#1A4175]" href="{{ url('/faq') }}">FAQ</a>
                <a class="text-[#191b22]/70 transition-colors hover:text-[#1A4175]" href="{{ url('/resources') }}">Resources</a>
                <a class="text-[#191b22]/70 transition-colors hover:text-[#1A4175]" href="{{ url('/support') }}">Support</a>
            </div>
            <div class="flex items-center gap-6">
                <a class="font-semibold text-[#191b22]/70 transition-colors hover:text-[#1A4175]" href="{{ route('login') }}">Login</a>
                <a class="rounded-lg bg-gradient-to-br from-primary to-primary-container px-6 py-2.5 font-bold text-on-primary shadow-sm transition-transform hover:-translate-y-0.5" href="{{ route('register') }}">Sign Up</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow px-6 pb-20 pt-32">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-20 lg:grid-cols-2">
            <div class="hidden space-y-8 lg:block">
                <div class="space-y-4">
                    <span class="inline-flex items-center gap-2 rounded-full bg-secondary-container/30 px-3 py-1 text-xs font-bold uppercase tracking-wider text-on-secondary-container">
                        <span class="material-symbols-outlined text-sm">school</span> Academic Excellence
                    </span>
                    <h1 class="text-6xl font-extrabold leading-[1.1] tracking-tighter text-primary">
                        Your Journey <br /> Towards Mastery <br /> Begins Here.
                    </h1>
                </div>
                <p class="max-w-md text-lg leading-relaxed text-on-surface-variant">
                    Join the modern ecosystem for scholars and educators. A refined space designed for clarity, data-driven insights, and academic growth.
                </p>
                <div class="grid grid-cols-2 gap-8 pt-6">
                    <div class="space-y-2">
                        <div class="text-3xl font-bold text-primary">240+</div>
                        <div class="text-sm text-on-surface-variant">Affiliated Institutions</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-3xl font-bold text-primary">1.2M</div>
                        <div class="text-sm text-on-surface-variant">Active Learners</div>
                    </div>
                </div>
                <div class="relative mt-12 h-64 overflow-hidden rounded-2xl bg-surface-container shadow-2xl">
                    <img alt="Modern university library" class="absolute inset-0 h-full w-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDj6DZ-UjWMPrOZQmFO6yd1e_JTa44PRPFWL2-JyUCV1j80YjtSstIcnyCBS3Nq86f68lXEvVDVwKraYHX_p31xXWkXnc_q3_2QTT48wp9Lp3VOi_YXd-0yBuCSTd-nG1NK2Een6sKd__TVDd0t7_frojb4THaA89o7C7pPVSarKamQ2sFk5YBf99qa3echYqnVStX95mEK9txWNc383LCKSi7WMV4jJBL0ya--yPojCPd5m3PPkRVnbcxIwnxF6EC62r8oEvA-L-z2" />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/40 to-transparent"></div>
                </div>
            </div>

            <div class="flex w-full justify-center">
                <div
                    class="w-full max-w-xl rounded-[2rem] border-none bg-surface-container-lowest p-8 shadow-none md:p-12"
                >
                    <div class="mb-10">
                        <h2 class="mb-2 text-3xl font-bold tracking-tight text-primary">Create Account</h2>
                        <p class="text-on-surface-variant">Join the global community of Modern Scholars.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-error-container bg-error-container px-5 py-4 text-sm text-on-error-container">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-10 flex rounded-xl bg-surface-container-low p-1.5">
                        <button
                            type="button"
                            class="toggle-button flex-1 rounded-lg px-4 py-3 transition-all"
                            data-role="student"
                            id="studentToggle"
                        >
                            Student
                        </button>
                        <button
                            type="button"
                            class="toggle-button flex-1 rounded-lg px-4 py-3 transition-all"
                            data-role="user"
                            id="staffToggle"
                        >
                            Staff
                        </button>
                    </div>

                    <form class="space-y-6" method="POST" action="{{ route('register') }}">
                        @csrf
                        <input type="hidden" id="usertypeInput" name="usertype" value="{{ old('usertype', 'user') }}" />

                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-2">
                                <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Full Name</label>
                                <input class="w-full rounded-xl border-none bg-surface-container-highest px-6 py-4 transition-all placeholder:text-outline/50 focus:border-b-2 focus:border-primary focus:ring-0" name="name" placeholder="Dr. Julian Vane" type="text" value="{{ old('name') }}" required autocomplete="name" autofocus />
                            </div>

                            <div class="space-y-2">
                                <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Institutional Email</label>
                                <input class="w-full rounded-xl border-none bg-surface-container-highest px-6 py-4 transition-all placeholder:text-outline/50 focus:border-b-2 focus:border-primary focus:ring-0" name="email" placeholder="j.vane@university.edu" type="email" value="{{ old('email') }}" required autocomplete="username" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2" id="studentFields" data-cloak>
                                <div class="space-y-2">
                                    <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Department</label>
                                    <select
                                        id="departmentField"
                                        class="w-full appearance-none rounded-xl border-none bg-surface-container-highest px-6 py-4 transition-all focus:border-b-2 focus:border-primary focus:ring-0"
                                        name="department"
                                    >
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department') == $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Level</label>
                                    <select
                                        id="levelField"
                                        class="w-full appearance-none rounded-xl border-none bg-surface-container-highest px-6 py-4 transition-all focus:border-b-2 focus:border-primary focus:ring-0"
                                        name="level"
                                    >
                                        <option value="">Select Level</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Password</label>
                                    <div class="relative">
                                        <input class="w-full rounded-xl border-none bg-surface-container-highest px-6 py-4 pr-14 transition-all placeholder:text-outline/50 focus:border-b-2 focus:border-primary focus:ring-0" id="passwordField" name="password" placeholder="********" type="password" required autocomplete="new-password" />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant password-toggle"
                                            data-target="passwordField"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block px-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Confirm Password</label>
                                    <div class="relative">
                                        <input class="w-full rounded-xl border-none bg-surface-container-highest px-6 py-4 pr-14 transition-all placeholder:text-outline/50 focus:border-b-2 focus:border-primary focus:ring-0" id="passwordConfirmationField" name="password_confirmation" placeholder="********" type="password" required autocomplete="new-password" />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-4 text-on-surface-variant password-toggle"
                                            data-target="passwordConfirmationField"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="pt-2">
                                <label class="flex items-start gap-3 text-sm text-on-surface-variant" for="terms">
                                    <input class="mt-1 rounded border-outline-variant text-primary focus:ring-primary" id="terms" name="terms" type="checkbox" required />
                                    <span>
                                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-semibold text-primary underline decoration-primary-container/30">'.__('Terms of Service').'</a>',
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-semibold text-primary underline decoration-primary-container/30">'.__('Privacy Policy').'</a>',
                                        ]) !!}
                                    </span>
                                </label>
                            </div>
                        @endif

                        <div class="pt-6">
                            <button class="w-full rounded-xl bg-gradient-to-br from-primary to-primary-container py-5 text-lg font-bold text-on-primary shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl" type="submit">
                                Create My Account
                            </button>
                            <p class="mt-6 text-center text-sm text-on-surface-variant">
                                Already registered?
                                <a class="font-semibold text-primary underline decoration-primary-container/30" href="{{ route('login') }}">Login here</a>.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="flex w-full flex-col items-center justify-between gap-6 bg-[#f2f3fd] px-12 py-12 font-['Inter'] text-sm md:flex-row">
        <div class="mb-6 md:mb-0">
            <span class="font-['Manrope'] text-lg font-bold text-[#1A4175]">Educore</span>
            <p class="mt-2 text-[#191b22]/60">&copy; 2024 Educore Management Systems. All rights reserved.</p>
        </div>
        <div class="flex gap-8">
            <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175] underline" href="#">Privacy Policy</a>
            <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175]" href="#">Terms of Service</a>
            <a class="text-[#191b22]/60 transition-colors hover:text-[#1A4175]" href="#">Contact Support</a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departments = @json($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]));
            const toggleButtons = document.querySelectorAll('.toggle-button');
            const usertypeInput = document.getElementById('usertypeInput');
            const studentFields = document.getElementById('studentFields');
            const departmentField = document.getElementById('departmentField');
            const levelField = document.getElementById('levelField');
            const oldLevel = "{{ old('level', '') }}";

            function getCategoryByName(name) {
                if (!name) return 'regular';
                const lower = name.toLowerCase();
                const medKeys = ['medicine', 'medcine', 'nurs', 'nutrition', 'diet', 'laboratory', 'lab', 'public health', 'health'];

                for (const key of medKeys) {
                    if (lower.includes(key)) return 'medicine';
                }

                if (lower.includes('engineering')) return 'engineering';
                return 'regular';
            }

            function computeAllowedLevels(category) {
                let max = 400;
                if (category === 'medicine') max = 600;
                else if (category === 'engineering') max = 500;

                const levels = [];
                for (let value = 100; value <= max; value += 100) {
                    levels.push(String(value));
                }

                return levels;
            }

            function formatLevelLabel(level) {
                if (level === '100') return 'Undergraduate';
                if (level === '200') return 'Postgraduate';
                if (level === '300') return 'Doctorate';
                return `${level} Level`;
            }

            function updateToggleStyles(role) {
                toggleButtons.forEach((button) => {
                    const active = button.dataset.role === role;
                    button.classList.toggle('bg-surface-container-lowest', active);
                    button.classList.toggle('text-primary', active);
                    button.classList.toggle('font-bold', active);
                    button.classList.toggle('shadow-sm', active);
                    button.classList.toggle('text-on-surface-variant', !active);
                    button.classList.toggle('font-medium', !active);
                });
            }

            function populateLevels() {
                const selectedDepartment = departments.find((department) => Number(department.id) === Number(departmentField.value));
                const allowedLevels = computeAllowedLevels(getCategoryByName(selectedDepartment ? selectedDepartment.name : ''));
                const currentValue = levelField.value || oldLevel;

                levelField.innerHTML = '<option value="">Select Level</option>';

                allowedLevels.forEach((level) => {
                    const option = document.createElement('option');
                    option.value = level;
                    option.textContent = formatLevelLabel(level);

                    if (currentValue === level) {
                        option.selected = true;
                    }

                    levelField.appendChild(option);
                });

                if (!allowedLevels.includes(levelField.value)) {
                    levelField.value = '';
                }

                levelField.disabled = usertypeInput.value !== 'student' || allowedLevels.length === 0;
            }

            function switchRole(role) {
                usertypeInput.value = role;
                updateToggleStyles(role);

                if (role === 'student') {
                    studentFields.removeAttribute('data-cloak');
                    studentFields.style.display = '';
                    departmentField.disabled = false;
                    populateLevels();
                    return;
                }

                studentFields.style.display = 'none';
                departmentField.value = '';
                departmentField.disabled = true;
                levelField.innerHTML = '<option value="">Select Level</option>';
                levelField.value = '';
                levelField.disabled = true;
            }

            toggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    switchRole(button.dataset.role);
                });
            });

            departmentField.addEventListener('change', populateLevels);

            document.querySelectorAll('.password-toggle').forEach((button) => {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.dataset.target);
                    const icon = button.querySelector('.material-symbols-outlined');
                    const nextType = target.type === 'password' ? 'text' : 'password';
                    target.type = nextType;
                    icon.textContent = nextType === 'password' ? 'visibility' : 'visibility_off';
                });
            });

            switchRole(usertypeInput.value || 'user');
        });
    </script>
</body>
</html>
