<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Euvion demo request</title></head>
<body style="font-family:Arial,sans-serif;color:#191b22;line-height:1.6;max-width:680px;margin:24px auto;padding:20px">
<h1 style="color:#002b59">New Euvion demo request</h1>
<p>A visitor has asked to learn more about Euvion. Reply to this email to contact them.</p>
<table cellpadding="8" style="border-collapse:collapse;width:100%;text-align:left">
@foreach(['name' => 'Contact name', 'email' => 'Email', 'institution' => 'Institution', 'role' => 'Role', 'student_count' => 'Student population'] as $key => $label)
<tr><th style="vertical-align:top;border-bottom:1px solid #ddd">{{ $label }}</th><td style="border-bottom:1px solid #ddd">{{ $details[$key] ?? 'Not provided' }}</td></tr>
@endforeach
</table>
<h2>Areas of interest</h2>
@if($details['topics'])<ul>@foreach($details['topics'] as $topic)<li>{{ $topic }}</li>@endforeach</ul>@else<p>General Euvion walkthrough.</p>@endif
<h2>What they would like to discuss</h2>
<p style="white-space:pre-wrap">{{ $details['message'] ?? 'No additional details provided.' }}</p>
<p>The requester agreed to be contacted about this demonstration. A demo time has not been booked.</p>
</body></html>
