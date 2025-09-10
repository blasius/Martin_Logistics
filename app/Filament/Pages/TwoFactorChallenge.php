<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use Filament\Notifications\Notification;

class TwoFactorChallenge extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected string $view = 'filament.pages.two-factor-challenge';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-lock-closed';
    protected static bool $shouldRegisterNavigation = false;

    public $otp;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('otp')
                ->label('One-Time Password')
                ->required()
                ->numeric()
                ->placeholder('Enter 6-digit code'),
        ];
    }

    public function submit()
    {
        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        if (!$google2fa->verifyKey(decrypt($user->two_factor_secret), $this->otp)) {
            session(['two_factor_passed' => true]);

            Notification::make()
                ->title('2FA Verified')
                ->success()
                ->send();

            return redirect()->intended(config('filament.home_url'));
        }

        Notification::make()
            ->title('Invalid OTP code')
            ->danger()
            ->send();
    }
}
