<?php

namespace App\Http\Responses;

class TwoFactorLoginResponse extends \Laravel\Fortify\Http\Responses\TwoFactorLoginResponse
{
    public function toResponse($request)
    {
        if ($request->user()?->isStaff() && ! $request->wantsJson()) {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return parent::toResponse($request);
    }
}
