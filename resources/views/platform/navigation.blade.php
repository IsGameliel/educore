@php
    $platformAdmin = auth('platform')->user();
    $platformLinks = ['platform.index' => 'Overview', 'platform.institutions.index' => 'Institutions', 'platform.plans.index' => 'Plans', 'platform.billing' => 'Billing', 'platform.operations' => 'Operations', 'platform.reports' => 'Reports', 'platform.administrators.index' => 'Administrators', 'platform.audit' => 'Audit Log', 'platform.account.show' => 'My Account'];
@endphp
<p class="sidebar-heading">Platform administration</p><nav aria-label="Platform navigation" class="platform-navigation">
@foreach($platformLinks as $name => $label)
    @if($platformAdmin->canAccessPlatformRoute($name))
    <a href="{{ route($name) }}" @if(($name === 'platform.plans.index' && request()->routeIs('platform.plans.*')) || request()->routeIs($name) || ($name === 'platform.account.show' && request()->routeIs('platform.account.*')) || ($name === 'platform.institutions.index' && request()->routeIs('platform.institutions.*'))) aria-current="page" @endif>{{ $label }}</a>
    @endif
@endforeach
</nav>
<div class="sidebar-account muted"><strong>{{ $platformAdmin->name }}</strong>{{ \App\Models\Central\PlatformAdministrator::ROLES[$platformAdmin->role] ?? $platformAdmin->role }} @if(! $platformAdmin->two_factor_confirmed_at)<a href="{{ route('platform.account.show') }}">Enable two-factor authentication</a>@endif</div>
