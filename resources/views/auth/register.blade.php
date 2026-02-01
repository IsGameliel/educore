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
        <div class="mt-4">
            <x-label for="password" value="{{ __('Password') }}" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
        </div>

        {{-- Confirm password --}}
        <div class="mt-4">
            <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
            <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
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
