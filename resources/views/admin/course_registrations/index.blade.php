{{-- resources/views/admin/course_registrations/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            Course Registrations (Students)
        </h2>
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
                <div class="flex items-center justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Students</h3>
                        <p class="text-sm text-gray-300">Manage a student’s registered courses per semester.</p>
                    </div>

                    {{-- Optional: quick filter/search --}}
                    <form method="GET" action="{{ route('admin.course-registrations.index') }}" class="flex gap-2">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search name/email..."
                            class="rounded-lg border border-gray-600 bg-black/20 text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <button class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm">
                            Search
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-300 border-b border-white/10">
                                <th class="py-3 pr-4">Name</th>
                                <th class="py-3 pr-4">Email</th>
                                <th class="py-3 pr-4">Department</th>
                                <th class="py-3 pr-4">Level</th>
                                <th class="py-3 pr-4">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            @forelse($students as $student)
                                <tr class="text-gray-100">
                                    <td class="py-3 pr-4 font-medium">
                                        {{ $student->name }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $student->email }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ optional($student->department)->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $student->level ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <a
                                            href="{{ route('admin.course-registrations.show', $student->id) }}?semester=First"
                                            class="inline-flex items-center rounded-lg bg-gray-800 hover:bg-gray-700 px-3 py-2 text-xs text-white"
                                        >
                                            Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-300">
                                        No students found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
