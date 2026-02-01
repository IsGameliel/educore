{{-- resources/views/admin/course_registrations/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                Edit Registration — {{ $student->name }} ({{ $semester }} Semester)
            </h2>

            <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester={{ $semester }}"
               class="rounded-lg bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 text-sm">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- errors --}}
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-600/20 p-4 text-red-200">
                    <div class="font-semibold mb-2">Please fix these:</div>
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white/5 backdrop-blur rounded-xl shadow p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div class="text-gray-200">
                        <div class="text-sm text-gray-300">Student</div>
                        <div class="text-lg font-semibold text-white">{{ $student->name }}</div>
                        <div class="text-sm text-gray-300">{{ $student->email }}</div>
                        <div class="text-sm text-gray-300">
                            Dept: {{ optional($student->department)->name ?? 'N/A' }} •
                            Level: {{ $student->level ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.course-registrations.edit', $student->id) }}?semester=First"
                           class="rounded-lg px-4 py-2 text-sm {{ $semester === 'First' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-200 hover:bg-gray-700' }}">
                            First Semester
                        </a>

                        <a href="{{ route('admin.course-registrations.edit', $student->id) }}?semester=Second"
                           class="rounded-lg px-4 py-2 text-sm {{ $semester === 'Second' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-200 hover:bg-gray-700' }}">
                            Second Semester
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.course-registrations.update', $student->id) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="semester" value="{{ $semester }}">

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-300 border-b border-white/10">
                                    <th class="py-3 pr-4">Select</th>
                                    <th class="py-3 pr-4">Code</th>
                                    <th class="py-3 pr-4">Title</th>
                                    <th class="py-3 pr-4">Credit Unit</th>
                                    <th class="py-3 pr-4">Prerequisites</th>
                                    <th class="py-3 pr-4">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-white/10">
                                @forelse($courses as $course)
                                    @php
                                        $checked = in_array($course->id, $registeredCourseIds);

                                        $prereqTitles = $course->prerequisites->pluck('title')->toArray();

                                        // ✅ pick current status if registered, otherwise default
                                        // You'll pass $registeredStatuses from controller (recommended)
                                        $currentStatus = $registeredStatuses[$course->id] ?? 'registered';
                                    @endphp

                                    <tr class="text-gray-100">
                                        <td class="py-3 pr-4">
                                            <input
                                                type="checkbox"
                                                name="course_ids[]"
                                                value="{{ $course->id }}"
                                                {{ $checked ? 'checked' : '' }}
                                                class="h-4 w-4 rounded border-gray-500 bg-black/20 text-indigo-600 focus:ring-indigo-500"
                                            >
                                        </td>

                                        <td class="py-3 pr-4 font-medium">
                                            {{ $course->code }}
                                        </td>

                                        <td class="py-3 pr-4">
                                            {{ $course->title }}
                                        </td>

                                        <td class="py-3 pr-4">
                                            {{ $course->credit_unit }}
                                        </td>

                                        <td class="py-3 pr-4 text-gray-300">
                                            @if(count($prereqTitles))
                                                <ul class="list-disc ml-5">
                                                    @foreach($prereqTitles as $t)
                                                        <li>{{ $t }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                None
                                            @endif
                                        </td>

                                        {{-- ✅ Status dropdown --}}
                                        <td class="py-3 pr-4">
                                            <select
                                                name="statuses[{{ $course->id }}]"
                                                class="rounded-lg border border-gray-600 bg-black/20 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                <option value="registered" {{ $currentStatus === 'registered' ? 'selected' : '' }}>Registered</option>
                                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                <option value="withdrawn" {{ $currentStatus === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                            </select>

                                            <div class="text-xs text-gray-400 mt-1">
                                                Tip: Only applies if course is selected.
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-300">
                                            No courses available for this student’s dept/level/semester.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester={{ $semester }}"
                           class="rounded-lg bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 text-sm">
                            Cancel
                        </a>

                        <button type="submit"
                                class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 text-sm font-semibold">
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
