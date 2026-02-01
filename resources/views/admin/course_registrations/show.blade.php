{{-- resources/views/admin/course_registrations/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ $student->name }} — Registered Courses ({{ $semester }} Semester)
            </h2>

            <a href="{{ route('admin.course-registrations.index') }}"
               class="rounded-lg bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 text-sm">
                Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- flash --}}
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-600/20 p-4 text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- errors --}}
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-600/20 p-4 text-red-200">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white/5 backdrop-blur rounded-xl shadow p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
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
                        {{-- Semester Switch --}}
                        <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester=First"
                           class="rounded-lg px-4 py-2 text-sm {{ $semester === 'First' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-200 hover:bg-gray-700' }}">
                            First Semester
                        </a>

                        <a href="{{ route('admin.course-registrations.show', $student->id) }}?semester=Second"
                           class="rounded-lg px-4 py-2 text-sm {{ $semester === 'Second' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-200 hover:bg-gray-700' }}">
                            Second Semester
                        </a>

                        <a href="{{ route('admin.course-registrations.edit', $student->id) }}?semester={{ $semester }}"
                           class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm">
                            Edit Courses
                        </a>
                    </div>
                </div>

                <div class="mb-4 text-gray-200">
                    <span class="text-gray-300">Total Credit Units:</span>
                    <span class="font-semibold text-white">{{ $totalCredits }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-300 border-b border-white/10">
                                <th class="py-3 pr-4">Code</th>
                                <th class="py-3 pr-4">Title</th>
                                <th class="py-3 pr-4">Credit Unit</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            @forelse($registrations as $reg)
                                <tr class="text-gray-100">
                                    <td class="py-3 pr-4 font-medium">
                                        {{ $reg->course->code ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $reg->course->title ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $reg->course->credit_unit ?? 0 }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center rounded-full bg-gray-800 px-3 py-1 text-xs text-gray-200">
                                            {{ $reg->status ?? 'registered' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-300">
                                        No registered courses for {{ $semester }} Semester.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
