<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['verification_required' => true, 'redirect' => route('verification.notice')], 201)
            : redirect()->route('verification.notice');
    }
}
