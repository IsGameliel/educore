<x-guest-layout>
<x-authentication-card>
<x-slot name="logo">
    <x-authentication-card-logo />
</x-slot>

    <x-validation-errors class="mb-4" />

    {{-- Alpine root: role, selectedDepartment (id), level, departments data --}}
    <form method="POST" action="{{ route('register') }}"
          x-data="registrationForm()"
          x-init="init()"
          class="space-y-4">
        @csrf

        {{-- Role selection --}}
        <div class="mt-4">
            <x-label for="role" value="{{ __('I am registering as:') }}" />
            <div class="flex space-x-6 mt-2">
                <label for="role_user" class="flex items-center cursor-pointer">
                    <input type="radio" name="usertype" id="role_user" value="user" x-model="role"
                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600" required>
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ __('General User') }}</span>
                </label>

                <label for="role_student" class="flex items-center cursor-pointer">
                    <input type="radio" name="usertype" id="role_student" value="student" x-model="role"
                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600">
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ __('Student') }}</span>
                </label>
            </div>
        </div>

        {{-- Name --}}
        <div>
            <x-label for="name" value="{{ __('Name') }}" />
            <x-input id="name" class="block mt-1 w-full" type="text" name="name"
                     :value="old('name')" required autofocus autocomplete="name" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-label for="email" value="{{ __('Email') }}" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email"
                     :value="old('email')" required autocomplete="username" />
        </div>

        {{-- Department (student only) --}}
        <div class="mt-4" x-cloak x-show="role === 'student'"
             x-transition:enter.duration.300ms x-transition:leave.duration.200ms>
            <x-label for="department" value="{{ __('Department') }}" />
            <div class="mt-1 relative">
                <select id="department" name="department"
                        x-model.number="selectedDepartment"
                        @change="onDepartmentChange"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 pr-10 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('-- Select Department --') }}</option>

                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @if(old('department') == $department->id) selected @endif>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-xs text-gray-500">
                    {{ __('Select your department to load appropriate levels.') }}
                </p>
            </div>
        </div>

        {{-- Level (student only) --}}
        <div class="mt-4" x-cloak x-show="role === 'student'"
             x-transition:enter.duration.300ms x-transition:leave.duration.200ms>
            <x-label for="level" value="{{ __('Level') }}" />
            <div class="mt-1 relative">
                <select id="level" name="level" x-model="level"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        x-bind:disabled="allowedLevels.length === 0">
                    <option value="">{{ __('-- Select Level --') }}</option>
                    <template x-for="lv in allowedLevels" :key="lv">
                        <option :value="lv" x-text="lv + ' Level'"></option>
                    </template>
                </select>

                <p class="mt-2 text-xs text-gray-500" x-text="allowedLevels.length ? '' : 'Select a department to load levels.'"></p>
            </div>
        </div>

        {{-- Password --}}
        <div class="mt-4" x-data="{ showPassword: false }">
            <x-label for="password" value="{{ __('Password') }}" />
            <div class="relative mt-1">
                <x-input id="password" class="block w-full pr-12" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" />
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

        {{-- Confirm password --}}
        <div class="mt-4" x-data="{ showPasswordConfirmation: false }">
            <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
            <div class="relative mt-1">
                <x-input id="password_confirmation" class="block w-full pr-12" x-bind:type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 transition hover:text-gray-700 focus:outline-none focus:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 dark:focus:text-gray-200"
                    x-on:click="showPasswordConfirmation = !showPasswordConfirmation"
                    x-bind:aria-label="showPasswordConfirmation ? 'Hide password confirmation' : 'Show password confirmation'"
                    x-bind:title="showPasswordConfirmation ? 'Hide password confirmation' : 'Show password confirmation'"
                >
                    <svg x-show="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 4.5c-4.37 0-8.06 2.9-9.34 6.88a1.1 1.1 0 0 0 0 .24C1.94 15.6 5.63 18.5 10 18.5s8.06-2.9 9.34-6.88a1.1 1.1 0 0 0 0-.24C18.06 7.4 14.37 4.5 10 4.5Zm0 11.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z" />
                        <path d="M10 8.5A1.5 1.5 0 1 0 11.5 10 1.5 1.5 0 0 0 10 8.5Z" />
                    </svg>
                    <svg x-show="showPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M3.28 2.22a.75.75 0 1 0-1.06 1.06l2.07 2.07A10.43 10.43 0 0 0 .66 11.38a1.1 1.1 0 0 0 0 .24C1.94 15.6 5.63 18.5 10 18.5a9.9 9.9 0 0 0 4.42-1.01l2.3 2.29a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM10 16.99c-3.6 0-6.72-2.3-7.83-5.49a8.96 8.96 0 0 1 3.2-4.36l1.78 1.78A3.5 3.5 0 0 0 11.08 12l2.27 2.27a8.46 8.46 0 0 1-3.35.72Zm.05-10.49a3.4 3.4 0 0 1 3.45 3.45c0 .55-.13 1.08-.37 1.55l3.06 3.06a8.98 8.98 0 0 0 1.64-3.06 1.1 1.1 0 0 0 0-.24C16.56 8.4 13.74 6.27 10.45 6l-.4-.4Z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Terms --}}
        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
            <div class="mt-4">
                <x-label for="terms">
                    <div class="flex items-center">
                        <x-checkbox name="terms" id="terms" required />

                        <div class="ms-2">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                    'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">'.__('Terms of Service').'</a>',
                                    'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </div>
                    </div>
                </x-label>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-button class="ms-4">
                {{ __('Register') }}
            </x-button>
        </div>
    </form>

    {{-- Alpine script --}}
    <script>
        function registrationForm() {
            return {
                // initial values (server old() are applied here)
                role: "{{ old('role', 'user') }}",
                // departments: array of {id, name} from server
                departments: @json($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])),
                // selectedDepartment is numeric id or empty string
                selectedDepartment: "{{ old('department', '') }}" ? Number("{{ old('department', '') }}") : '',
                // level value (e.g., 100, 200, etc.) - old kept if valid later
                level: "{{ old('level', '') }}" ? String("{{ old('level', '') }}") : '',
                allowedLevels: [],

                // categorization by name (case-insensitive)
                getCategoryByName(name) {
                    if (!name) return 'regular';
                    const lower = name.toLowerCase();

                    // medicine-related keywords
                    const medKeys = ['medicine', 'medcine', 'nurs', 'nutrition', 'diet', 'laboratory', 'lab', 'public health', 'health'];
                    for (const k of medKeys) {
                        if (lower.includes(k)) return 'medicine';
                    }

                    // engineering keyword
                    if (lower.includes('engineering')) return 'engineering';

                    // fallback
                    return 'regular';
                },

                // compute allowed level numbers based on category
                computeAllowedLevels(category) {
                    let max = 400;
                    if (category === 'medicine') max = 600;
                    else if (category === 'engineering') max = 500;

                    const arr = [];
                    for (let v = 100; v <= max; v += 100) arr.push(v);
                    return arr;
                },

                // called on init and when department changes
                updateAllowedLevels() {
                    // find department object by id
                    const dep = this.departments.find(d => Number(d.id) === Number(this.selectedDepartment));
                    const name = dep ? dep.name : '';
                    const category = this.getCategoryByName(name);

                    const newAllowed = this.computeAllowedLevels(category);

                    // S2 behavior: keep current level if still valid, otherwise reset
                    if (this.level && newAllowed.includes(Number(this.level))) {
                        this.allowedLevels = newAllowed;
                        // keep level as string
                        this.level = String(this.level);
                    } else {
                        this.allowedLevels = newAllowed;
                        this.level = '';
                    }
                },

                onDepartmentChange() {
                    this.updateAllowedLevels();
                },

                init() {
                    // initialize allowedLevels on load (if department preselected)
                    this.updateAllowedLevels();

                    // If old level exists but wasn't in allowedLevels, it will be cleared by updateAllowedLevels()
                }
            };
        }
    </script>

</x-authentication-card>
</x-guest-layout>
