<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta content="{{ csrf_token() }}" name="csrf-token"/>
    <title>Educore - Profile Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "on-error-container": "#93000a",
              "on-error": "#ffffff",
              "inverse-primary": "#c3c0ff",
              "tertiary-fixed": "#ffdbcc",
              "primary-fixed": "#e2dfff",
              "surface-container-highest": "#e4e1ee",
              "tertiary-container": "#a44100",
              "surface-dim": "#dcd8e5",
              "secondary-fixed": "#d3e4fe",
              "surface-container": "#f0ecf9",
              "on-tertiary-fixed-variant": "#7b2f00",
              "on-primary": "#ffffff",
              "on-secondary-fixed-variant": "#38485d",
              "secondary-fixed-dim": "#b7c8e1",
              "surface-container-high": "#eae6f4",
              "primary-container": "#4f46e5",
              "primary": "#3525cd",
              "outline-variant": "#c7c4d8",
              "background": "#fcf8ff",
              "on-tertiary-fixed": "#351000",
              "primary-fixed-dim": "#c3c0ff",
              "on-primary-container": "#dad7ff",
              "surface-container-low": "#f5f2ff",
              "on-secondary-fixed": "#0b1c30",
              "on-background": "#1b1b24",
              "tertiary-fixed-dim": "#ffb695",
              "surface-bright": "#fcf8ff",
              "surface": "#fcf8ff",
              "on-surface": "#1b1b24",
              "on-tertiary": "#ffffff",
              "on-surface-variant": "#464555",
              "tertiary": "#7e3000",
              "on-secondary": "#ffffff",
              "surface-container-lowest": "#ffffff",
              "surface-tint": "#4d44e3",
              "secondary-container": "#d0e1fb",
              "inverse-surface": "#302f39",
              "inverse-on-surface": "#f3effc",
              "on-secondary-container": "#54647a",
              "on-primary-fixed-variant": "#3323cc",
              "secondary": "#505f76",
              "outline": "#777587",
              "on-primary-fixed": "#0f0069",
              "error": "#ba1a1a",
              "surface-variant": "#e4e1ee",
              "on-tertiary-container": "#ffd2be",
              "error-container": "#ffdad6"
            },
            borderRadius: {
              DEFAULT: "0.25rem",
              lg: "0.5rem",
              xl: "0.75rem",
              full: "9999px"
            },
            spacing: {
              xl: "2rem",
              xs: "0.25rem",
              sm: "0.5rem",
              "container-max": "1280px",
              "2xl": "3rem",
              base: "4px",
              gutter: "1.5rem",
              md: "1rem",
              lg: "1.5rem"
            },
            fontFamily: {
              "body-lg": ["Inter"],
              "body-md": ["Inter"],
              "body-sm": ["Inter"],
              h2: ["Manrope"],
              "label-caps": ["Inter"],
              h3: ["Manrope"],
              h1: ["Manrope"]
            },
            fontSize: {
              "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
              "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
              "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
              h2: ["30px", {"lineHeight": "36px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
              "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
              h3: ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
              h1: ["36px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}]
            }
          }
        }
      }
    </script>
    @livewireStyles
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }

        .profile-shell .jetstream-form-grid {
            display: block;
        }

        .profile-shell .jetstream-form-card {
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
            box-shadow: none;
        }

        .profile-shell .jetstream-form-grid-inner {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }

        @media (min-width: 640px) {
            .profile-shell .jetstream-form-grid-inner {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .profile-shell .jetstream-form-grid-inner > .col-span-6 {
            grid-column: span 1 / span 1;
        }

        .profile-shell .jetstream-form-grid-inner > .sm\:col-span-4,
        .profile-shell .jetstream-form-grid-inner > .sm\:col-span-3,
        .profile-shell .jetstream-form-grid-inner > .sm\:col-span-2 {
            grid-column: span 1 / span 1;
        }

        .profile-shell .jetstream-form-grid-inner > .profile-field-full,
        .profile-shell .jetstream-form-grid-inner > .col-span-6.sm\:col-span-4:first-child {
            grid-column: 1 / -1;
        }

        .profile-shell .jetstream-form-grid-inner label {
            display: block;
            margin-bottom: 0.375rem;
            color: #64748b;
            font-size: 12px;
            line-height: 16px;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .profile-shell .jetstream-form-grid-inner input,
        .profile-shell .jetstream-form-grid-inner select {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 16px;
            line-height: 24px;
            color: #1e293b;
            background: #ffffff;
            box-shadow: none;
        }

        .profile-shell .jetstream-form-grid-inner input:focus,
        .profile-shell .jetstream-form-grid-inner select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .profile-shell .jetstream-form-grid-inner .text-xs,
        .profile-shell .jetstream-form-grid-inner .text-sm {
            color: #64748b;
        }

        .profile-shell .jetstream-form-grid-inner .text-red-600,
        .profile-shell .jetstream-form-grid-inner .dark\:text-red-400 {
            color: #dc2626 !important;
            font-size: 14px !important;
            line-height: 20px !important;
        }

        .profile-shell .jetstream-photo-block,
        .profile-shell .jetstream-field-card,
        .profile-shell .jetstream-action-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 1rem;
        }

        .profile-shell .jetstream-photo-actions,
        .profile-shell .jetstream-inline-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .profile-shell .jetstream-inline-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .profile-shell .jetstream-inline-row > :first-child {
            flex: 1 1 auto;
        }

        .profile-shell button,
        .profile-shell .inline-flex {
            align-items: center;
            justify-content: center;
        }

        .profile-shell .profile-card button[type="submit"],
        .profile-shell .profile-card .bg-gray-800,
        .profile-shell .profile-card .bg-red-600 {
            border: 0;
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
        }

        .profile-shell .profile-card .bg-gray-800 {
            background: #4f46e5 !important;
            color: #ffffff !important;
        }

        .profile-shell .profile-card .bg-red-600 {
            background: #ba1a1a !important;
            color: #ffffff !important;
        }

        .profile-shell .profile-card .bg-white {
            border-radius: 0.5rem;
        }

        .profile-shell .profile-card .bg-gray-50 {
            background: #f8fafc !important;
        }

        .profile-shell .jetstream-action-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 0 0;
            flex-wrap: wrap;
        }

        .profile-shell .jetstream-section-stack > * + * {
            margin-top: 1rem;
        }

        .profile-shell .jetstream-session-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
        }

        .profile-shell .jetstream-modal .rounded-lg {
            border-radius: 0.75rem;
        }

        .profile-shell .jetstream-modal .bg-white {
            background: #ffffff !important;
        }

        .profile-shell .jetstream-modal .px-6.py-4 {
            padding: 1.25rem 1.5rem;
        }

        @media (max-width: 767px) {
            .profile-shell .jetstream-inline-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
@php
    $currentUser = $user ?? auth()->user();
    $displayUser = $currentUser;
    $profileLabel = $displayUser->matric_number ?: strtoupper(substr((string) $displayUser->usertype, 0, 3)) . '-' . $displayUser->id;
@endphp
<body class="bg-surface-container-low text-on-surface antialiased">
    <header class="bg-white border-b border-slate-200 fixed top-0 w-full z-50 h-16 shadow-sm flex items-center justify-between px-6 antialiased">
        <div class="flex items-center gap-8">
            <span class="text-xl font-extrabold tracking-tight text-indigo-600">Educore</span>
            <nav class="hidden md:flex space-x-6">
                <a class="text-slate-500 font-medium hover:bg-slate-50 transition-colors px-3 py-1 rounded" href="{{ url('home') }}">Dashboard</a>
                <a class="text-slate-500 font-medium hover:bg-slate-50 transition-colors px-3 py-1 rounded" href="#">Courses</a>
                <a class="text-indigo-600 font-semibold border-b-2 border-indigo-600 px-3 py-1" href="{{ route('profile.show') }}">Profile</a>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors active:opacity-80" type="button">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <button class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors active:opacity-80" type="button">
                <span class="material-symbols-outlined">help_outline</span>
            </button>
            <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200">
                <img alt="{{ $displayUser->name }}" class="w-full h-full object-cover" src="{{ $displayUser->profile_photo_url }}"/>
            </div>
        </div>
    </header>

    <aside class="fixed left-0 top-16 h-[calc(100vh-64px)] w-64 border-r border-slate-200 bg-slate-50 flex flex-col p-4 space-y-2 text-sm hidden lg:flex">
        <div class="mb-6 px-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($displayUser->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-slate-900 leading-none">Educore Portal</p>
                    <p class="text-slate-500 text-xs mt-1">{{ $profileLabel }}</p>
                </div>
            </div>
        </div>
        <a class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition-transform translate-x-0.5" href="{{ url('home') }}">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 bg-white text-indigo-600 shadow-sm rounded-lg font-bold transition-transform translate-x-0.5" href="{{ route('profile.show') }}">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
        <div class="mt-auto pt-4 border-t border-slate-200">
            <a class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg" href="{{ url('home') }}">
                <span class="material-symbols-outlined">arrow_back</span>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </aside>

    <main class="lg:ml-64 pt-16 min-h-screen profile-shell">
        <div class="max-w-7xl mx-auto px-6 py-10 space-y-10">
            <section>
                <h1 class="font-h1 text-h1 text-on-surface">Profile Settings</h1>
                <p class="text-body-md text-on-surface-variant mt-2">Manage your academic identity, security preferences, and active sessions.</p>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <nav class="md:col-span-3 space-y-1">
                    <a class="flex items-center gap-3 px-4 py-2.5 text-indigo-600 bg-white shadow-sm rounded-lg font-semibold" href="#profile-info">
                        <span class="material-symbols-outlined">person_outline</span>
                        <span>Profile Information</span>
                    </a>
                    <a class="flex items-center gap-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 rounded-lg" href="#security">
                        <span class="material-symbols-outlined">security</span>
                        <span>Security</span>
                    </a>
                    <a class="flex items-center gap-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 rounded-lg" href="#browser-sessions">
                        <span class="material-symbols-outlined">devices</span>
                        <span>Browser Sessions</span>
                    </a>
                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures() && auth()->user()->usertype !== 'student')
                        <a class="flex items-center gap-3 px-4 py-2.5 text-slate-600 hover:bg-slate-50 rounded-lg" href="#delete-account">
                            <span class="material-symbols-outlined">delete</span>
                            <span>Delete Account</span>
                        </a>
                    @endif
                </nav>

                <div class="md:col-span-9 space-y-8">
                    <section class="profile-card bg-white rounded-xl shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1)] overflow-hidden" id="profile-info">
                        <div class="px-6 py-5 border-b border-slate-100">
                            <h3 class="font-h3 text-h3 text-on-surface">Profile Information</h3>
                            <p class="text-body-sm text-on-surface-variant mt-1">Update your account's profile information and email address.</p>
                        </div>
                        <div class="p-8">
                            @livewire('profile.update-profile-information-form')
                        </div>
                    </section>

                    <section class="profile-card bg-white rounded-xl shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1)] overflow-hidden" id="security">
                        <div class="px-6 py-5 border-b border-slate-100">
                            <h3 class="font-h3 text-h3 text-on-surface">Update Password</h3>
                            <p class="text-body-sm text-on-surface-variant mt-1">Ensure your account is using a long, random password to stay secure.</p>
                        </div>
                        <div class="p-8">
                            @livewire('profile.update-password-form')
                        </div>
                    </section>

                    <section class="profile-card bg-white rounded-xl shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1)] overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100">
                            <h3 class="font-h3 text-h3 text-on-surface">Two Factor Authentication</h3>
                            <p class="text-body-sm text-on-surface-variant mt-1">Add additional security to your account using two factor authentication.</p>
                        </div>
                        <div class="p-8">
                            @livewire('profile.two-factor-authentication-form')
                        </div>
                    </section>

                    <section class="profile-card bg-white rounded-xl shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1)] overflow-hidden" id="browser-sessions">
                        <div class="px-6 py-5 border-b border-slate-100">
                            <h3 class="font-h3 text-h3 text-on-surface">Browser Sessions</h3>
                            <p class="text-body-sm text-on-surface-variant mt-1">Manage and log out your active sessions on other browsers and devices.</p>
                        </div>
                        <div class="p-8">
                            @livewire('profile.logout-other-browser-sessions-form')
                        </div>
                    </section>

                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures() && auth()->user()->usertype !== 'student')
                        <section class="profile-card bg-white rounded-xl shadow-sm border border-error/20 overflow-hidden" id="delete-account">
                            <div class="px-6 py-5 border-b border-error/10 bg-error-container/5">
                                <h3 class="font-h3 text-h3 text-error">Delete Account</h3>
                                <p class="text-body-sm text-on-surface-variant mt-1">Permanently delete your account.</p>
                            </div>
                            <div class="p-8">
                                @livewire('profile.delete-user-form')
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>

        <footer class="h-20 border-t border-slate-200 flex items-center justify-center text-body-sm text-slate-400">
            © 2024 Educore Academic Systems. All rights reserved.
        </footer>
    </main>

    @livewireScripts
</body>
</html>
