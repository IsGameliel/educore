<?php

namespace App\Http\Responses;

class LoginResponse extends \Laravel\Fortify\Http\Responses\LoginResponse
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
