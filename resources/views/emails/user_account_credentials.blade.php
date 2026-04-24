<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Educore Account Details</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Hello, {{ $user->name }}</h2>

    @if($action === 'updated')
        <p>Your Educore account details were updated by an administrator.</p>
    @else
        <p>Your Educore account has been created successfully.</p>
    @endif

    @if($user->usertype === 'student')
        <p><strong>Faculty:</strong> {{ optional(optional($user->department)->faculty)->name ?: 'Not assigned' }}</p>
        <p><strong>Department:</strong> {{ optional($user->department)->name ?: 'Not assigned' }}</p>
        <p><strong>Level:</strong> {{ $user->level ?: 'Not assigned' }}</p>
        <p><strong>Matric Number:</strong> {{ $user->matric_number ?: 'Not assigned' }}</p>
    @else
        <p><strong>Role:</strong> {{ $user->role_name }}</p>
    @endif

    <p><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p><strong>Email:</strong> {{ $user->email }}</p>

    @if($plainPassword)
        <p><strong>Password:</strong> {{ $plainPassword }}</p>
    @else
        <p><strong>Password:</strong> Your existing password is unchanged.</p>
    @endif

    <p>Please log in and change your password after your next sign-in if needed.</p>

    <p>Regards,<br>Educore Team</p>
</body>
</html>
