<!DOCTYPE html>
<html>
<head>
    <title>New Class Scheduled</title>
</head>
<body>
    <h1>Hello, {{ $studentName }}</h1>
    <p>A new class has been scheduled for your department:</p>
    <ul>
        <li><strong>Subject:</strong> {{ $courseTitle }}</li>
        <li><strong>Department:</strong> {{ $schedule->department?->name }}</li>
        <li><strong>Level:</strong> {{ $schedule->level }}</li>
        <li><strong>Semester:</strong> {{ $schedule->semester }}</li>
        <li><strong>Day:</strong> {{ $schedule->day }}</li>
        <li><strong>Time:</strong> {{ $schedule->start_time }} - {{ $schedule->end_time }}</li>
        <li><strong>Room:</strong> {{ $schedule->room }}</li>
        <li><strong>Lecturer:</strong> {{ $schedule->lecturer?->name ?? 'To be confirmed' }}</li>
    </ul>
    <p>Please make necessary arrangements to attend the class.</p>
</body>
</html>
