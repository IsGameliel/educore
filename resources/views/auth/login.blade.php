<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Welcome back') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Sign in to continue to your dashboard.') }}
            </p>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 font-medium text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div x-data="{ showPassword: false }">
                <x-label for="password" value="{{ __('Password') }}" />
                <div class="relative mt-2">
                    <x-input id="password" class="block w-full pr-12" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 transition hover:text-gray-700 focus:outline-none focus:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 dark:focus:text-gray-200"
                        x-on:click="showPassword = !showPassword"
                        x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                        x-bind:title="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 4.5c-4.37 0-8.06 2.9-9.34 6.88a1.1 1.1 0 0 0 0 .24C1.94 15.6 5.63 18.5 10 18.5s8.06-2.9 9.34-6.88a1.1 1.1 0 0 0 0-.24C18.06 7.4 14.37 4.5 10 4.5Zm0 11.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" />
                            <path d="M10 8.5A1.5 1.5 0 1 0 11.5 10 1.5 1.5 0 0 0 10 8.5Z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3.28 2.22a.75.75 0 1 0-1.06 1.06l2.07 2.07A10.43 10.43 0 0 0 .66 11.38a1.1 1.1 0 0 0 0 .24C1.94 15.6 5.63 18.5 10 18.5a9.9 9.9 0 0 0 4.42-1.01l2.3 2.29a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM10 16.99c-3.6 0-6.72-2.3-7.83-5.49a8.96 8.96 0 0 1 3.2-4.36l1.78 1.78A3.5 3.5 0 0 0 11.08 12l2.27 2.27a8.46 8.46 0 0 1-3.35.72Zm.05-10.49a3.4 3.4 0 0 1 3.45 3.45c0 .55-.13 1.08-.37 1.55l3.06 3.06a8.98 8.98 0 0 0 1.64-3.06 1.1 1.1 0 0 0 0-.24C16.56 8.4 13.74 6.27 10.45 6l-.4-.4Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <div class="pt-2">
                <x-button class="w-full justify-center">
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>

        <div class="mt-6 border-t border-gray-200 pt-5 text-center dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __("Don't have an account yet?") }}
                <a class="font-semibold text-indigo-600 transition hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 dark:focus:ring-offset-gray-800 rounded-md" href="{{ route('register') }}">
                    {{ __('Create one here') }}
                </a>
            </p>
        </div>
    </x-authentication-card>
</x-guest-layout>
