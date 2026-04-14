<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Educore Student Account</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Hello, {{ $student->name }}</h2>

    <p>Your student account has been created on Educore.</p>

    <p><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p><strong>Email:</strong> {{ $student->email }}</p>
    <p><strong>Password:</strong> {{ $plainPassword }}</p>
    <p><strong>Matric Number:</strong> {{ $student->matric_number ?: 'Not assigned' }}</p>

    <p>Please log in and change your password after your first sign-in.</p>

    <p>Regards,<br>Educore Team</p>
</body>
</html>
