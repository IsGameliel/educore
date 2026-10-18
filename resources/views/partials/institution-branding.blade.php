@if(config('institution.branding.name'))
    @php
        $brandColor = config('institution.branding.primary_color', '#174c85');
        if(!preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) { $brandColor = '#174c85'; }
    @endphp
    <style>
        :root { --institution-primary: {{ $brandColor }}; }
        .btn-primary, .bg-primary-container { background-color:var(--institution-primary)!important; border-color:var(--institution-primary)!important; }
        .text-primary { color:var(--institution-primary)!important; }
    </style>
@endif
