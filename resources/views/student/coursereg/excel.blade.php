<table>
    <thead>
        <tr>
            <th>Course Code</th>
            <th>Course Title</th>
            <th>Credit Unit</th>
            <th>Semester</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($courses as $course)
            <tr>
                <td>{{ $course->course->code }}</td>
                <td>{{ $course->course->title }}</td>
                <td>{{ $course->course->credit_unit }}</td>
                <td>{{ $course->course->semester }}</td>
                <td>{{ ucfirst($course->status) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
