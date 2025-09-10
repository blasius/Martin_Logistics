<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class Login extends BaseLogin
{
    public $otp; // one-time password input

    protected function getFormSchema(): array
    {
        $schema = parent::getFormSchema();

        $schema[] = \Filament\Forms\Components\TextInput::make('otp')
            ->label('One-Time Password')
            ->password()
            ->hidden(fn () => ! $this->hasTwoFactorEnabled())
            ->required(fn () => $this->hasTwoFactorEnabled());

        return $schema;
    }

    public function authenticate(): ?LoginResponse
    {
        parent::authenticate();

        $user = Auth::user();

        if ($user && $user->two_factor_secret) {
            $google2fa = app('pragmarx.google2fa');

            $valid = $google2fa->verifyKey(
                decrypt($user->two_factor_secret),
                $this->otp
            );

            if (! $valid) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'otp' => 'The provided two-factor authentication code is invalid.',
                ]);
            }
        }

        return app(LoginResponse::class);
    }

    private function hasTwoFactorEnabled(): bool
    {
        $user = Auth::getProvider()->retrieveByCredentials([
            'email' => $this->email,
        ]);

        return $user && $user->two_factor_secret;
    }
}
