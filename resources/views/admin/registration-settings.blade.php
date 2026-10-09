@extends('layouts.dash')

@section('content')
<div class="main-panel"><div class="content-wrapper"><div class="registration-settings">
    <div class="registration-settings__heading">
        <div>
            <div class="registration-settings__eyebrow">Academic administration</div>
            <h1>Course Registration Settings</h1>
            <p>Manage student registration access and semester fee requirements.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">Back to dashboard</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('admin.registration-settings.update') }}">
        @csrf
        @method('PUT')
        <div class="registration-settings__scope"><i class="mdi mdi-information-outline" aria-hidden="true"></i><span>Applies to all student registration submissions for their selected session and semester.</span></div>
        <section class="registration-settings__card" aria-labelledby="registration-access-title">
            <div class="registration-settings__row">
                <div class="registration-settings__description">
                    <div class="registration-settings__icon"><i class="mdi mdi-book-open-page-variant" aria-hidden="true"></i></div>
                    <div><h2 id="registration-access-title">Registration access</h2><p>Choose when students can submit their course registrations.</p></div>
                </div>
                <div class="registration-settings__control">
                    <input type="hidden" name="registration_open" value="0">
                    <label class="registration-toggle" for="registration_open">
                        <input type="checkbox" role="switch" id="registration_open" name="registration_open" value="1" aria-label="Course registration is open" aria-describedby="registration-access-help" @checked(old('registration_open', $settings->registration_open))>
                        <span class="registration-toggle__track" aria-hidden="true"></span>
                        <span class="registration-toggle__status" aria-hidden="true"><span class="registration-toggle__on">Open</span><span class="registration-toggle__off">Closed</span></span>
                    </label>
                </div>
            </div>
            <div class="registration-settings__detail" id="registration-access-help">
                <div><strong>When open</strong><p>Students can register for available courses.</p></div>
                <div><strong>When closed</strong><p>New registrations are blocked. Students can still view and download their registered courses.</p></div>
            </div>
        </section>
        <section class="registration-settings__card" aria-labelledby="registration-fees-title">
            <div class="registration-settings__row">
                <div class="registration-settings__description">
                    <div class="registration-settings__icon"><i class="mdi mdi-shield-check" aria-hidden="true"></i></div>
                    <div><h2 id="registration-fees-title">Pending fees</h2><p>Require tuition clearance before a student can register.</p></div>
                </div>
                <div class="registration-settings__control">
                    <input type="hidden" name="require_fee_clearance" value="0">
                    <label class="registration-toggle" for="require_fee_clearance">
                        <input type="checkbox" role="switch" id="require_fee_clearance" name="require_fee_clearance" value="1" aria-label="Block registration when required semester fees are unpaid" aria-describedby="registration-fees-help" @checked(old('require_fee_clearance', $settings->require_fee_clearance))>
                        <span class="registration-toggle__track" aria-hidden="true"></span>
                        <span class="registration-toggle__status" aria-hidden="true"><span class="registration-toggle__on">Required</span><span class="registration-toggle__off">Optional</span></span>
                    </label>
                </div>
            </div>
            <div class="registration-settings__detail" id="registration-fees-help">
                <div><strong>When required</strong><p>Unpaid semester fees block registration. Configured installments, waivers and approved exemptions apply.</p></div>
                <div><strong>When optional</strong><p>Students can register with pending fees while registration is open.</p></div>
            </div>
            <div class="registration-settings__note"><i class="mdi mdi-information-outline" aria-hidden="true"></i><p>Checks apply to issued tuition bills for the active session. Sessions without tuition billing do not require payment.</p></div>
        </section>
        <section class="registration-settings__card" aria-labelledby="late-registration-title">
            <div class="registration-settings__row">
                <div class="registration-settings__description">
                    <div class="registration-settings__icon"><i class="mdi mdi-clock-alert-outline" aria-hidden="true"></i></div>
                    <div><h2 id="late-registration-title">Late course registration</h2><p>Require a ₦5,000 late registration payment before students register.</p></div>
                </div>
                <div class="registration-settings__control">
                    <input type="hidden" name="require_late_registration_fee" value="0">
                    <label class="registration-toggle" for="require_late_registration_fee">
                        <input type="checkbox" role="switch" id="require_late_registration_fee" name="require_late_registration_fee" value="1" aria-label="Require the 5000 naira late registration fee" aria-describedby="late-registration-help" @checked(old('require_late_registration_fee', $settings->require_late_registration_fee))>
                        <span class="registration-toggle__track" aria-hidden="true"></span>
                        <span class="registration-toggle__status" aria-hidden="true"><span class="registration-toggle__on">On</span><span class="registration-toggle__off">Off</span></span>
                    </label>
                </div>
            </div>
            <div class="registration-settings__detail" id="late-registration-help">
                <div><strong>When on</strong><p>Students pay ₦5,000 through Paystack once per session and semester. Verified payment is required before registering courses.</p></div>
                <div><strong>When off</strong><p>No late registration fee is required. Existing tuition-clearance rules still apply.</p></div>
            </div>
        </section>
        <div class="registration-settings__footer">
            <div><strong>Ready to apply your changes?</strong><p>Changes take effect after saving. Closing registration overrides fee clearance.</p></div>
            <button type="submit" class="btn btn-primary">Save registration settings</button>
        </div>
    </form>
</div></div></div></div>
@endsection

@push('styles')
<style>
    .registration-settings { max-width: 1040px; margin: 0 auto; color: #172033; }
    .registration-settings__heading { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
    .registration-settings__eyebrow { color: #2357d9; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 10px; }
    .registration-settings h1 { font-size: clamp(24px, 3vw, 30px); font-weight: 700; line-height: 1.25; margin: 0 0 10px; }
    .registration-settings p { color: #64748b; font-size: 14px; line-height: 1.65; margin: 0; }
    .registration-settings__heading .btn { flex-shrink: 0; }
    .registration-settings__scope { display: flex; align-items: center; gap: 12px; background: #eaf0ff; color: #244b9b; border: 1px solid #dce6fd; padding: 16px 20px; border-radius: 12px; margin-bottom: 22px; font-size: 14px; line-height: 1.6; }
    .registration-settings__scope i { font-size: 22px; flex-shrink: 0; }
    .registration-settings__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(23, 32, 51, .035); overflow: hidden; }
    .registration-settings__row { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 28px; }
    .registration-settings__description { display: flex; align-items: center; gap: 16px; min-width: 0; }
    .registration-settings__icon { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; flex-shrink: 0; background: #f0f4ff; color: #2357d9; border-radius: 12px; font-size: 24px; }
    .registration-settings h2 { font-size: 18px; font-weight: 700; margin: 0 0 6px; line-height: 1.4; }
    .registration-settings__control { flex: 0 0 160px; display: flex; justify-content: flex-start; border-left: 1px solid #e2e8f0; padding-left: 24px; }
    .registration-toggle { position: relative; display: inline-flex; align-items: center; gap: 12px; min-height: 44px; cursor: pointer; margin: 0; user-select: none; }
    .registration-toggle input { position: absolute; left: 0; top: 0; width: 52px; height: 44px; opacity: 0; margin: 0; cursor: pointer; z-index: 1; }
    .registration-toggle__track { position: relative; display: block; width: 52px; height: 30px; flex-shrink: 0; background: #94a3b8; border-radius: 999px; transition: background .18s ease; }
    .registration-toggle__track::after { content: ''; position: absolute; top: 4px; left: 4px; width: 22px; height: 22px; background: #fff; border-radius: 50%; box-shadow: 0 1px 4px rgba(0, 0, 0, .16); transition: transform .18s ease; }
    .registration-toggle input:checked + .registration-toggle__track { background: #2357d9; }
    .registration-toggle input:checked + .registration-toggle__track::after { transform: translateX(22px); }
    .registration-toggle input:focus-visible + .registration-toggle__track { outline: 3px solid #93b4ff; outline-offset: 4px; }
    .registration-toggle__status { font-size: 13px; font-weight: 700; color: #64748b; }
    .registration-toggle__on { display: none; }
    .registration-toggle input:checked ~ .registration-toggle__status { color: #2357d9; }
    .registration-toggle input:checked ~ .registration-toggle__status .registration-toggle__on { display: inline; }
    .registration-toggle input:checked ~ .registration-toggle__status .registration-toggle__off { display: none; }
    .registration-settings__detail { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; padding: 22px 28px; background: #fafbfd; border-top: 1px solid #edf0f5; }
    .registration-settings__detail strong { display: block; color: #334155; font-size: 13px; margin-bottom: 6px; }
    .registration-settings__detail p, .registration-settings__note p { font-size: 13px; }
    .registration-settings__note { display: flex; align-items: flex-start; gap: 10px; padding: 16px 28px; border-top: 1px solid #edf0f5; }
    .registration-settings__note i { font-size: 18px; color: #64748b; flex-shrink: 0; }
    .registration-settings__footer { display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 8px 0; margin-top: 26px; }
    .registration-settings__footer strong { display: block; font-size: 14px; margin-bottom: 5px; }
    .registration-settings__footer p { font-size: 13px; }
    .registration-settings__footer .btn { flex-shrink: 0; min-height: 44px; }
    @media (max-width: 767px) {
        .registration-settings__heading, .registration-settings__footer { flex-direction: column; align-items: flex-start; gap: 16px; }
        .registration-settings__row { padding: 22px; flex-direction: column; align-items: flex-start; gap: 18px; }
        .registration-settings__control { flex: none; width: 100%; border-left: 0; border-top: 1px solid #edf0f5; padding: 14px 0 0; }
        .registration-settings__detail { grid-template-columns: 1fr; gap: 18px; padding: 20px 22px; }
        .registration-settings__note { padding: 16px 22px; }
        .registration-settings__footer .btn { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .registration-toggle__track, .registration-toggle__track::after { transition: none; }
    }
</style>
@endpush
