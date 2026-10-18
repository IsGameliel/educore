<a href="/" class="flex justify-center">
    @if(config('institution.branding.name'))
        @if(config('institution.branding.logo_path'))<img src="{{ route('institution.logo') }}" alt="{{ config('app.name') }}" class="block h-32 w-auto max-w-[320px] object-contain">
        @else<span class="text-2xl font-bold">{{ config('app.name') }}</span>@endif
    @else
    <img
        src="{{ asset('asset/images/euvion1.png') }}"
        alt="Euvion"
        class="block h-32 w-auto max-w-[320px] object-contain"
    >
    @endif
</a>
