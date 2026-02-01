<!DOCTYPE html>
<html>
<head>
    <title>New Class Scheduled</title>
</head>
<body>
    <h1>Hello, {{ $schedule->student_name }}</h1>
    <p>A new class has been scheduled for your department:</p>
    <ul>
        <li><strong>Subject:</strong> {{ $courseTitle }}</li>
        <li><strong>Day:</strong> {{ $schedule->day }}</li>
        <li><strong>Time:</strong> {{ $schedule->start_time }} - {{ $schedule->end_time }}</li>
        <li><strong>Room:</strong> {{ $schedule->room }}</li>
        <li><strong>Lecturer:</strong> {{ $schedule->lecturer->name }}</li>
    </ul>
    <p>Please make necessary arrangements to attend the class.</p>
</body>
</html>
