<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/firebase/health', function () {
    $auth = app('firebase.auth');
    $auth->listUsers(1); // just triggers a request
    return response()->json(['ok' => true]);
});
