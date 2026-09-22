<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission | Educore</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-extrabold text-blue-700">
                <span class="material-symbols-outlined">school</span>
                <span>Educore Admission</span>
            </a>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden text-slate-500 sm:inline">{{ $user->email }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-200 px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-8">
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($application)
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                Application <strong>{{ $application->application_number }}</strong> is {{ $application->status }}.
                @if($user->usertype === 'student')
                    Your account is already a student account.
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <strong class="mb-2 block">Please correct the highlighted fields.</strong>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <form method="POST" action="{{ route('admissions.store') }}" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Section 1</p>
                        <h1 class="mt-1 text-2xl font-extrabold">Personal Information</h1>
                    </div>
                    <div class="grid gap-5 p-6 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Surname</span>
                            <input name="surname" value="{{ old('surname', $application?->surname) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">First Name</span>
                            <input name="first_name" value="{{ old('first_name', $application?->first_name ?? $user->name) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Middle Name</span>
                            <input name="middle_name" value="{{ old('middle_name', $application?->middle_name) }}" class="w-full rounded-lg border-slate-300">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Date of Birth</span>
                            <input name="date_of_birth" value="{{ old('date_of_birth', optional($application?->date_of_birth)->format('Y-m-d')) }}" type="date" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Gender</span>
                            <select name="gender" class="w-full rounded-lg border-slate-300" required>
                                <option value="">Select gender</option>
                                @foreach(['Female', 'Male', 'Other'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender', $application?->gender) === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Phone Number</span>
                            <input name="phone" value="{{ old('phone', $application?->phone) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-sm font-semibold">Residential Address</span>
                            <input name="address" value="{{ old('address', $application?->address) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">City</span>
                            <input name="city" value="{{ old('city', $application?->city) }}" class="w-full rounded-lg border-slate-300">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">State</span>
                            <input name="state" value="{{ old('state', $application?->state) }}" class="w-full rounded-lg border-slate-300">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Country</span>
                            <input name="country" value="{{ old('country', $application?->country ?? 'Nigeria') }}" class="w-full rounded-lg border-slate-300">
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Section 2</p>
                        <h2 class="mt-1 text-2xl font-extrabold">Parent or Guardian Information</h2>
                    </div>
                    <div class="grid gap-5 p-6 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Parent/Guardian Name</span>
                            <input name="parent_name" value="{{ old('parent_name', $application?->parent_name) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Relationship</span>
                            <input name="parent_relationship" value="{{ old('parent_relationship', $application?->parent_relationship) }}" class="w-full rounded-lg border-slate-300" placeholder="Father, Mother, Guardian" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Parent Phone</span>
                            <input name="parent_phone" value="{{ old('parent_phone', $application?->parent_phone) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Parent Email</span>
                            <input name="parent_email" value="{{ old('parent_email', $application?->parent_email) }}" type="email" class="w-full rounded-lg border-slate-300">
                        </label>
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-sm font-semibold">Parent Address</span>
                            <input name="parent_address" value="{{ old('parent_address', $application?->parent_address) }}" class="w-full rounded-lg border-slate-300">
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Section 3</p>
                        <h2 class="mt-1 text-2xl font-extrabold">Academic Section</h2>
                    </div>
                    <div class="grid gap-5 p-6 md:grid-cols-2">
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-sm font-semibold">Applicant Type</span>
                            <select id="admissionTypeField" name="admission_type" class="w-full rounded-lg border-slate-300" required>
                                <option value="">Select applicant type</option>
                                <option value="fresh" @selected(old('admission_type', $application?->admission_type ?? 'fresh') === 'fresh')>Fresh Student</option>
                                <option value="direct_entry" @selected(old('admission_type', $application?->admission_type) === 'direct_entry')>Direct Entry Student</option>
                                <option value="transfer" @selected(old('admission_type', $application?->admission_type) === 'transfer')>Transfer Student</option>
                                <option value="foreign" @selected(old('admission_type', $application?->admission_type) === 'foreign')>Foreign Student</option>
                            </select>
                            <p id="admissionTypeHint" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"></p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Department</span>
                            <select id="departmentField" name="department_id" class="w-full rounded-lg border-slate-300" required>
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id', $application?->department_id) === (string) $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Entry Level</span>
                            <select id="levelField" name="level" class="w-full rounded-lg border-slate-300" required>
                                <option value="">Select level</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Entry Year</span>
                            <input name="entry_year" value="{{ old('entry_year', $application?->entry_year ?? now()->year) }}" type="number" min="1900" max="{{ now()->year }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Previous School</span>
                            <input name="previous_school" value="{{ old('previous_school', $application?->previous_school) }}" class="w-full rounded-lg border-slate-300" required>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Qualification</span>
                            <select id="qualificationField" name="qualification" class="w-full rounded-lg border-slate-300" required>
                                <option value="">Select qualification</option>
                                @foreach(['Olevel', 'ND', 'Diploma'] as $qualification)
                                    <option value="{{ $qualification }}" @selected(old('qualification', $application?->qualification) === $qualification)>{{ $qualification }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold">Graduation Year</span>
                            <input name="graduation_year" value="{{ old('graduation_year', $application?->graduation_year) }}" type="number" min="1900" max="{{ now()->year }}" class="w-full rounded-lg border-slate-300">
                        </label>
                        <div id="olevelBlock" class="hidden rounded-lg border border-blue-100 bg-blue-50 p-4 md:col-span-2">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-bold text-blue-950">Olevel Subjects and Grades</h3>
                                    <p class="text-sm text-blue-800">Add each subject and its grade.</p>
                                </div>
                                <button id="addOlevelRow" type="button" class="rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">
                                    Add Subject
                                </button>
                            </div>
                            <div id="olevelRows" class="mt-4 space-y-3"></div>
                        </div>
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-sm font-semibold">Academic Notes</span>
                            <textarea name="academic_notes" rows="4" class="w-full rounded-lg border-slate-300" placeholder="Awards, transfer details, or special notes">{{ old('academic_notes', $application?->academic_notes) }}</textarea>
                        </label>
                        <div class="grid gap-5 md:col-span-2 md:grid-cols-3">
                            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900 md:col-span-3">
                                <strong class="block">Supporting documents</strong>
                                <span id="documentHint">Upload any documents that apply to your entry route.</span>
                                <p class="mt-2">PDF, JPG or PNG, up to 5 MB per file. Uploads are optional; after a validation error, select your files again.</p>
                            </div>
                            <label class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <span class="block text-sm font-semibold">JAMB Result</span>
                                <input name="jamb_result" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-300 bg-white text-sm">
                                <span class="block text-xs text-slate-500">Recommended for fresh applicants.</span>
                                @if($application?->jamb_result_path)
                                    <span class="block text-xs font-medium text-emerald-700">Uploaded</span>
                                @endif
                            </label>
                            <label class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <span class="block text-sm font-semibold">Diploma Result/Certificate</span>
                                <input name="diploma_result" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-300 bg-white text-sm">
                                <span class="block text-xs text-slate-500">Recommended for ND, Diploma, or direct entry applicants.</span>
                                @if($application?->diploma_result_path)
                                    <span class="block text-xs font-medium text-emerald-700">Uploaded</span>
                                @endif
                            </label>
                            <label class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <span class="block text-sm font-semibold">Transcript</span>
                                <input name="transcript" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-300 bg-white text-sm">
                                <span class="block text-xs text-slate-500">Recommended for transfer and foreign applicants.</span>
                                @if($application?->transcript_path)
                                    <span class="block text-xs font-medium text-emerald-700">Uploaded</span>
                                @endif
                            </label>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-3 rounded-lg border border-blue-100 bg-blue-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-bold text-blue-950">Submit admission application</h3>
                        <p class="text-sm text-blue-800">Once submitted, your account becomes a student account.</p>
                    </div>
                    <button class="rounded-lg bg-blue-700 px-6 py-3 font-bold text-white shadow-sm hover:bg-blue-800" type="submit">
                        Complete Admission
                    </button>
                </div>
            </form>

            <aside class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-extrabold">Admission Steps</h2>
                    <ol class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="font-bold text-blue-700">1.</span> Create an account.</li>
                        <li class="flex gap-3"><span class="font-bold text-blue-700">2.</span> Choose your applicant type and fill all sections.</li>
                        <li class="flex gap-3"><span class="font-bold text-blue-700">3.</span> Submit to activate student access.</li>
                    </ol>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-extrabold">Current Account</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Name</dt>
                            <dd class="font-semibold">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Status</dt>
                            <dd class="font-semibold capitalize">{{ $user->usertype }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Email</dt>
                            <dd class="font-semibold break-all">{{ $user->email }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departments = {{ Illuminate\Support\Js::from($departments->map(fn ($department) => ['id' => $department->id, 'name' => $department->name])) }};
            const admissionTypeField = document.getElementById('admissionTypeField');
            const admissionTypeHint = document.getElementById('admissionTypeHint');
            const documentHint = document.getElementById('documentHint');
            const departmentField = document.getElementById('departmentField');
            const levelField = document.getElementById('levelField');
            const qualificationField = document.getElementById('qualificationField');
            const olevelBlock = document.getElementById('olevelBlock');
            const olevelRows = document.getElementById('olevelRows');
            const addOlevelRow = document.getElementById('addOlevelRow');
            const selectedLevel = {{ Illuminate\Support\Js::from((string) old('level', $application?->level)) }};
            const savedOlevelResults = {{ Illuminate\Support\Js::from(old('olevel_subjects') ? collect(old('olevel_subjects'))->map(fn ($subject, $index) => ['subject' => $subject, 'grade' => old('olevel_grades.' . $index)])->values() : ($application?->olevel_results ?? [])) }};
            const olevelGrades = ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'];
            const olevelSubjects = [
                'Mathematics',
                'English Language',
                'Biology',
                'Chemistry',
                'Physics',
                'Economics',
                'Government',
                'Literature in English',
                'Geography',
                'Civic Education',
                'Agricultural Science',
                'Commerce',
                'Accounting',
                'Christian Religious Studies',
                'Islamic Religious Studies',
                'Computer Studies',
                'Further Mathematics',
            ];
            const applicantHints = {
                fresh: {
                    type: 'Fresh students usually enter with Olevel results and should upload a JAMB result where available.',
                    docs: 'Fresh applicants should upload JAMB Result and add Olevel subjects/grades when Olevel is selected.',
                },
                direct_entry: {
                    type: 'Direct entry students can apply with ND or Diploma qualifications and supporting certificates.',
                    docs: 'Direct entry applicants should upload Diploma Result/Certificate. A transcript can also be uploaded if available.',
                },
                transfer: {
                    type: 'Transfer students should provide previous institution details and academic records.',
                    docs: 'Transfer applicants should upload a transcript and any result/certificate from the previous institution.',
                },
                foreign: {
                    type: 'Foreign students should provide country information and international academic records.',
                    docs: 'Foreign applicants should upload transcripts and certificate/result documents where available.',
                },
            };

            function categoryFor(name) {
                const lower = String(name || '').toLowerCase();
                if (['medicine', 'nurs', 'nutrition', 'laboratory', 'public health', 'health'].some((key) => lower.includes(key))) {
                    return 'medicine';
                }

                if (lower.includes('engineering')) {
                    return 'engineering';
                }

                return 'regular';
            }

            function levelsFor(category) {
                const max = category === 'medicine' ? 600 : category === 'engineering' ? 500 : 400;
                const levels = [];
                for (let level = 100; level <= max; level += 100) {
                    levels.push(String(level));
                }
                return levels;
            }

            function refreshLevels() {
                const department = departments.find((item) => Number(item.id) === Number(departmentField.value));
                const levels = levelsFor(categoryFor(department ? department.name : ''));
                const current = levelField.value || selectedLevel;
                levelField.innerHTML = '<option value="">Select level</option>';

                levels.forEach((level) => {
                    const option = document.createElement('option');
                    option.value = level;
                    option.textContent = level + ' Level';
                    option.selected = current === level;
                    levelField.appendChild(option);
                });
            }

            departmentField.addEventListener('change', refreshLevels);
            refreshLevels();

            function refreshApplicantGuidance() {
                const hint = applicantHints[admissionTypeField.value] || applicantHints.fresh;
                admissionTypeHint.textContent = hint.type;
                documentHint.textContent = hint.docs;
                document.querySelector('[name=country]').required = admissionTypeField.value === 'foreign';
            }

            admissionTypeField.addEventListener('change', refreshApplicantGuidance);
            refreshApplicantGuidance();

            function createOlevelRow(result = {}) {
                const row = document.createElement('div');
                row.className = 'grid gap-3 rounded-lg border border-blue-100 bg-white p-3 sm:grid-cols-[1fr_150px_auto]';

                const subject = document.createElement('select');
                subject.name = 'olevel_subjects[]';
                subject.setAttribute('aria-label', 'Olevel subject');
                subject.required = qualificationField.value === 'Olevel';
                subject.disabled = qualificationField.value !== 'Olevel';
                subject.className = 'w-full rounded-lg border-slate-300';
                subject.innerHTML = '<option value="">Select subject</option>';
                const subjects = result.subject && !olevelSubjects.includes(result.subject)
                    ? [...olevelSubjects, result.subject] : olevelSubjects;
                subjects.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    option.selected = result.subject === item;
                    subject.appendChild(option);
                });

                const grade = document.createElement('select');
                grade.name = 'olevel_grades[]';
                grade.setAttribute('aria-label', 'Olevel grade');
                grade.required = qualificationField.value === 'Olevel';
                grade.disabled = qualificationField.value !== 'Olevel';
                grade.className = 'w-full rounded-lg border-slate-300';
                grade.innerHTML = '<option value="">Grade</option>';
                olevelGrades.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    option.selected = result.grade === item;
                    grade.appendChild(option);
                });

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100';
                remove.textContent = 'Remove';
                remove.addEventListener('click', function () {
                    row.remove();
                    ensureOlevelRow();
                });

                row.append(subject, grade, remove);
                olevelRows.appendChild(row);
            }

            function ensureOlevelRow() {
                if (qualificationField.value === 'Olevel' && !olevelRows.children.length) {
                    createOlevelRow();
                }
            }

            function toggleOlevelBlock() {
                const show = qualificationField.value === 'Olevel';
                olevelBlock.classList.toggle('hidden', !show);
                ensureOlevelRow();
                olevelRows.querySelectorAll('select').forEach((field) => {
                    field.required = show;
                    field.disabled = !show;
                });
            }

            addOlevelRow.addEventListener('click', function () {
                createOlevelRow();
            });

            qualificationField.addEventListener('change', toggleOlevelBlock);

            if (savedOlevelResults.length) {
                savedOlevelResults.forEach((result) => createOlevelRow(result));
            }

            toggleOlevelBlock();
        });
    </script>
</body>
</html>
