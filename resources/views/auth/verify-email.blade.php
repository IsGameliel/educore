<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email | Educore</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f7fb;color:#172940;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.6}a{color:inherit}button,input{font:inherit}button{cursor:pointer}button:disabled{cursor:wait;opacity:.65}a:focus-visible,button:focus-visible{outline:3px solid #82aaf3;outline-offset:4px}.header{max-width:1200px;margin:auto;padding:28px 32px;display:flex;align-items:center;justify-content:space-between}.brand{display:flex;gap:10px;align-items:center;font-size:23px;font-weight:800;text-decoration:none;letter-spacing:-.7px}.brand svg{width:34px;height:34px;color:#245bc0}.header-note{font-size:13px;color:#64748b}.shell{max-width:1020px;margin:40px auto 64px;display:grid;grid-template-columns: .9fr 1.1fr;background:white;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;box-shadow:0 24px 70px #17365c0c}.intro{padding:52px 38px;background:#102f58;color:#fff;display:flex;flex-direction:column;justify-content:space-between;gap:54px}.eyebrow{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#acc7ed}.intro h2{font-size:34px;line-height:1.25;letter-spacing:-1px;margin:18px 0}.intro p{font-size:14px;color:#c0cfe3}.steps{list-style:none;padding:0;margin:30px 0 0;display:grid;gap:22px}.steps li{display:flex;align-items:center;gap:13px;font-size:14px}.step-number{width:30px;height:30px;display:grid;place-items:center;border:1px solid #6c87aa;border-radius:50%;font-size:12px}.complete .step-number{background:#224d65;color:#b6eed2;border-color:#39716f}.active .step-number{background:white;color:#173c70;border-color:white}.pending{color:#aabbd1}.intro-footer{border-top:1px solid #ffffff24;padding-top:20px;font-size:12px;color:#b7c9df}.content{padding:48px}.mail-icon{width:58px;height:58px;background:#edf3ff;border:1px solid #dce7fa;color:#285ab3;border-radius:16px;display:grid;place-items:center;margin-bottom:24px}.mail-icon svg{width:28px;height:28px}h1{font-size:28px;letter-spacing:-.8px;line-height:1.25;margin:0 0 14px}.description{font-size:14px;color:#64748b;margin:0 0 26px}.email{display:block;color:#263f61;font-weight:600;overflow-wrap:anywhere;margin-top:5px}.notice{padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px;border:1px solid}.success{background:#effaf3;color:#216441;border-color:#c7e8d2}.error{background:#fff3f2;color:#a03535;border-color:#f4d0cc}.notice ul{margin:0;padding-left:18px}label{display:block;font-size:13px;font-weight:650;margin-bottom:8px}.code{width:100%;min-width:0;border:1px solid #ccd6e3;border-radius:10px;background:#fbfcff;padding:15px 10px;text-align:center;letter-spacing:12px;text-indent:12px;font-size:27px;font-weight:650;color:#19385e;transition:border-color .2s,box-shadow .2s}.code:focus{outline:none;border-color:#4477cd;box-shadow:0 0 0 4px #4477cd17}.code[aria-invalid=true]{border-color:#c45454}.field-hint{font-size:12px;color:#738197;margin:9px 0 23px}.primary{width:100%;border:0;border-radius:10px;background:#214e91;color:white;font-size:14px;font-weight:650;padding:14px 18px}.primary:hover{background:#183f78}.resend-area{margin-top:25px;text-align:center;font-size:13px;color:#728096}.resend-area p{margin:0 0 5px}.text-button{background:none;border:0;color:#245bb2;font-size:13px;font-weight:650;padding:5px 8px}.help{padding-top:23px;margin-top:23px;border-top:1px solid #edf0f5;font-size:12px;color:#738197;text-align:center}.logout{text-align:center;margin-top:20px}.logout button{color:#65758b;font-weight:500}.footer{text-align:center;color:#8190a3;font-size:12px;padding:0 24px 30px}@media(max-width:760px){.header{padding:22px}.header-note{display:none}.shell{margin:16px 20px 35px;grid-template-columns:1fr;border-radius:18px}.intro{padding:26px;gap:0}.intro h2{font-size:24px;margin:10px 0}.intro p,.intro-footer{display:none}.steps{display:flex;justify-content:space-between;gap:8px;margin-top:20px}.steps li{font-size:11px;gap:6px}.step-number{width:23px;height:23px;flex-shrink:0}.content{padding:30px 26px}h1{font-size:25px}}@media(max-width:380px){.steps li{flex-direction:column;text-align:center}.content{padding:26px 20px}}
    </style>
</head>
<body>
    @php
        $account = auth()->user();
        $hasCode = $account->email_otp_hash && $account->email_otp_expires_at?->isFuture() && $account->email_otp_address === $account->email;
        $resendSeconds = $account->email_otp_sent_at ? max(0, (int) ceil(now()->diffInSeconds($account->email_otp_sent_at->copy()->addSeconds(60), false))) : 0;
    @endphp
    <header class="header">
        <a class="brand" href="{{ url('/') }}" aria-label="Educore home">
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m3 12 13-7 13 7-13 7-13-7Z" fill="currentColor"/><path d="M8 17v7c5 4 11 4 16 0v-7M29 13v10" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/></svg>
            Educore
        </a>
        <span class="header-note">Your education. Your next chapter.</span>
    </header>
    <main class="shell">
        <aside class="intro" aria-label="Application progress">
            <div>
                <span class="eyebrow">Get started with Educore</span>
                <h2>Your next chapter<br>starts here.</h2>
                <p>Verify your email to take the next step in your admission journey.</p>
                <ol class="steps">
                    <li class="complete"><span class="step-number" aria-hidden="true">✓</span> Create account</li>
                    <li class="active" aria-current="step"><span class="step-number">2</span> Verify email</li>
                    <li class="pending"><span class="step-number">3</span> Admission form</li>
                </ol>
            </div>
            <div class="intro-footer">One account for your academic journey.</div>
        </aside>
        <section class="content" aria-labelledby="verification-title">
            <div class="mail-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg></div>
            <h1 id="verification-title">Check your inbox</h1>
            <p class="description">
                @if ($hasCode)
                    Enter the six-digit verification code sent to
                @else
                    Request a verification code to continue with
                @endif
                <span class="email">{{ $account->email }}</span>
            </p>
            @if (session('status') === 'verification-code-sent')
                <div role="status" class="notice success">A new verification code has been sent. Please check your inbox.</div>
            @endif
            @if (session('otp_error'))
                <div role="alert" class="notice error">{{ session('otp_error') }}</div>
            @elseif (! $hasCode && ! $errors->any())
                <div role="status" class="notice error">You don’t have an active code. Select Resend Code to request a new one.</div>
            @endif
            @if ($errors->any())
                <div role="alert" id="verification-errors" class="notice error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ route('verification.otp') }}" id="verify-form">
                @csrf
                <label for="code">Verification code</label>
                <input class="code" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus placeholder="000000" aria-describedby="code-help{{ $errors->any() ? ' verification-errors' : '' }}" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}">
                <p class="field-hint" id="code-help">Enter all 6 digits. Your code is valid for 10 minutes.</p>
                <button class="primary" type="submit">Verify and continue &nbsp; →</button>
            </form>
            <div class="resend-area">
                <p>Didn’t receive your code?</p>
                <form method="POST" action="{{ route('verification.send') }}" id="resend-form">
                    @csrf
                    <button class="text-button" id="resend-button" type="submit">Resend Code</button>
                </form>
                <span id="resend-status"></span>
            </div>
            <div class="help">Check your spam or junk folder. If you request another code, use the most recent one.</div>
            <form class="logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-button" type="submit">Log out</button>
            </form>
        </section>
    </main>
    <footer class="footer">&copy; {{ now()->year }} Educore. Built for your academic journey.</footer>
    <script>
        const resendButton = document.getElementById('resend-button');
        const resendStatus = document.getElementById('resend-status');
        const availableAt = Date.now() + {{ $resendSeconds }} * 1000;
        function updateCooldown() {
            const remaining = Math.max(0, Math.ceil((availableAt - Date.now()) / 1000));
            resendButton.disabled = remaining > 0;
            resendStatus.textContent = remaining > 0 ? `You can resend in ${remaining}s` : '';
            return remaining;
        }
        if (updateCooldown()) {
            const timer = setInterval(() => { if (!updateCooldown()) clearInterval(timer); }, 1000);
        }
        document.getElementById('resend-form').addEventListener('submit', () => {
            resendButton.disabled = true;
            resendButton.textContent = 'Sending…';
        });
        document.getElementById('verify-form').addEventListener('submit', (event) => {
            const button = event.currentTarget.querySelector('button');
            button.disabled = true;
            button.textContent = 'Verifying…';
        });
    </script>
</body>
</html>
