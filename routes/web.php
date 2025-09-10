<?php

use App\Filament\Pages\TwoFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-2fa', function () {
    $user = auth()->user();
    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    return '2FA enabled for ' . $user->email;
})->middleware('auth');

Route::get('/admin/2fa-challenge', TwoFactorChallenge::class)
    ->middleware(['auth'])
    ->name('filament.2fa.challenge');
