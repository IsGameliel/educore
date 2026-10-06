<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1050" height="660" viewBox="0 0 1050 660">
    <defs><clipPath id="photo"><rect x="55" y="223" width="230" height="280" rx="10"/></clipPath></defs>
    <rect width="1050" height="660" fill="#ffffff"/>
    <rect width="1050" height="180" fill="#123c58"/>
    <rect y="180" width="1050" height="8" fill="#caaa55"/>
    <image x="42" y="24" width="128" height="128" xlink:href="{{ $logo }}"/>
    <g font-family="Arial, sans-serif">
        <text x="195" y="83" fill="#ffffff" font-size="38" font-weight="bold">MUDIAME UNIVERSITY</text>
        <text x="197" y="124" fill="#d5e3ec" font-size="21" letter-spacing="3">TEMPORARY STUDENT ID CARD</text>
        <rect x="55" y="223" width="230" height="280" rx="10" fill="#edf2f6"/>
        @if($photo)
        <image x="55" y="223" width="230" height="280" preserveAspectRatio="xMidYMid slice" clip-path="url(#photo)" xlink:href="{{ $photo }}"/>
        @else
        <text x="170" y="368" text-anchor="middle" font-size="20" fill="#768899">Photo required</text>
        @endif
        <text x="330" y="250" fill="#768899" font-size="18" letter-spacing="2">STUDENT NAME</text>
        @php
            $nameLines = explode("\n", wordwrap($student->name, 32, "\n", true));
            $lineHeight = min(32, 72 / max(1, count($nameLines)));
            $nameSize = min(30, $lineHeight * .8);
        @endphp
        @foreach($nameLines as $line)
        <text x="330" y="{{ 284 + $loop->index * $lineHeight }}" fill="#123c58" font-size="{{ $nameSize }}" font-weight="bold">{{ $line }}</text>
        @endforeach
        <text x="330" y="367" fill="#768899" font-size="16" letter-spacing="2">MATRIC NUMBER</text>
        <text x="330" y="397" fill="#123c58" font-size="{{ mb_strlen($student->matric_number ?? '') > 30 ? 21 : 25 }}" font-weight="bold">{{ $student->matric_number ?: 'Not assigned' }}</text>
        <text x="330" y="435" fill="#768899" font-size="16" letter-spacing="2">DEPARTMENT</text>
        <text x="330" y="465" fill="#123c58" font-size="22" font-weight="bold" @if(mb_strlen($student->department?->name ?? '') > 43) textLength="650" lengthAdjust="spacingAndGlyphs" @endif>{{ $student->department?->name ?? 'Not assigned' }}</text>
        <text x="330" y="498" fill="#768899" font-size="15">LEVEL <tspan fill="#123c58" font-weight="bold">{{ $student->level ? $student->level.' L' : 'Not assigned' }}</tspan></text>
        <text x="590" y="498" fill="#768899" font-size="15">EXPIRES <tspan fill="{{ $expired ? '#b63e45' : '#123c58' }}" font-weight="bold">{{ $expiresAt?->format('d M Y') ?? 'Not available' }}</tspan></text>
        <rect x="55" y="535" width="940" height="1" fill="#dfe7ed"/>
        <text x="55" y="582" fill="#123c58" font-size="20" font-weight="bold" letter-spacing="2">TEMPORARY IDENTIFICATION</text>
        <text x="55" y="618" fill="#768899" font-size="16">For student identification while awaiting a permanent university ID card.</text>
        @if(! count($missing) && ! $expired)
        <image x="875" y="536" width="120" height="120" xlink:href="{{ $stamp }}"/>
        @endif
    </g>
</svg>
