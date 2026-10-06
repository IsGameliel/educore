@extends('layouts.dash')
@push('styles')
<style>
.student-id-page{max-width:1300px;margin:auto;width:100%}.id-header{background:#123c58;color:#fff;padding:30px;border-radius:16px;margin-bottom:24px}.id-header h1{font-size:28px;color:#fff;margin:0 0 10px}.id-header p{color:#d5e3ec;margin:0;line-height:1.7}.id-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(260px,1fr);gap:24px;align-items:start}.id-preview,.id-downloads{background:white;border:1px solid #e3eaf1;border-radius:16px;padding:26px}.id-preview h2,.id-downloads h2{font-size:18px;margin-bottom:20px}.id-card-frame{border-radius:12px;overflow:hidden;box-shadow:0 12px 28px #123c5820;border:1px solid #e3eaf1}.id-card-frame svg{width:100%;height:auto;display:block}.id-downloads p{font-size:13px;color:#748599;line-height:1.8}.id-actions{display:grid;gap:12px;margin:22px 0}.id-actions .btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px}.id-note{font-size:12px;color:#748599;line-height:1.7;margin:18px 0 0}.id-incomplete{border-radius:10px;padding:16px;background:#fff7e8;border:1px solid #f2dfba;color:#906519;margin-bottom:22px;font-size:13px;line-height:1.8}.id-incomplete a{font-weight:700;color:#70501d}.id-error{color:#b63e45!important}.id-downloads a:focus-visible,.id-downloads button:focus-visible{outline:3px solid #9ab7ee;outline-offset:3px}@media(max-width:1000px){.id-layout{grid-template-columns:1fr}}@media(max-width:600px){.student-id-page{padding:16px!important}.id-header{padding:24px}.id-preview,.id-downloads{padding:18px}}
</style>
@endpush
@section('content')
<div class="main-panel"><div class="content-wrapper student-id-page">
    <header class="id-header"><h1>Temporary student ID</h1><p>Your university identification card, ready to save or print.</p></header>
    @if($expired)<div class="id-incomplete"><strong>Your temporary ID card has expired.</strong> Contact student administration for assistance.</div>@endif
    @if(count($missing))<div class="id-incomplete"><strong>Your card needs a few details.</strong><br>Missing: {{ implode(', ', $missing) }}. <a href="{{ route('profile.show') }}">Update your profile photo</a>; contact student administration to correct your name, matric number, department or level. Downloads become available when all required details are present.</div>@endif
    <div class="id-layout"><section class="id-preview"><h2>Card preview</h2><div class="id-card-frame" role="img" aria-label="Temporary ID card for {{ $student->name }}">@include('student.id-card.card')</div><p class="id-note">Check your name, photo and matric number before downloading.</p></section>
    <section class="id-downloads"><h2>Download your card</h2><p>Save a PDF for printing or a high resolution PNG image for your phone.</p><div class="id-actions">
        @if(! count($missing) && ! $expired)
        <a class="btn btn-primary" href="{{ route('student.id-card.pdf') }}"><i class="mdi mdi-file-pdf" aria-hidden="true"></i> Download PDF</a>
        <button type="button" class="btn btn-outline-primary" id="download-id-image" data-url="{{ route('student.id-card.image') }}" data-filename="{{ $filename }}.png"><i class="mdi mdi-image" aria-hidden="true"></i> Download image (PNG)</button>
        @else
        <button class="btn btn-primary" disabled>Download PDF</button><button class="btn btn-outline-primary" disabled>Download image (PNG)</button>
        @endif
    </div><p id="id-download-status" role="status" aria-live="polite"></p><hr><p class="id-note">{{ $student->temporary_id_expires_at ? 'Your saved expiry date stays the same on subsequent downloads.' : 'The expiry shown is based on your current level and will be saved on your first download.' }} This card is temporary identification while you await your permanent university ID.</p><a href="{{ route('profile.show') }}" class="btn btn-link px-0">Manage profile photo <i class="mdi mdi-arrow-right" aria-hidden="true"></i></a></section></div>
</div></div></div>
@endsection
@section('scripts')
<script src="{{ asset('dash/assets/js/student-id-card.js') }}" defer></script>

@endsection
