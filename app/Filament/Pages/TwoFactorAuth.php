<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuth extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-check';
    protected string $view = 'filament.pages.two-factor-auth';
    protected static string|null|\UnitEnum $navigationGroup = 'Account Settings';

    public $user;

    public function mount(): void
    {
        $this->user = Auth::user();
    }

    public function enable2FA(): void
    {
        $this->user->enableTwoFactorAuthentication();

        Notification::make()
            ->title('Two-factor authentication enabled')
            ->success()
            ->send();
    }

    public function disable2FA(): void
    {
        $this->user->disableTwoFactorAuthentication();

        Notification::make()
            ->title('Two-factor authentication disabled')
            ->danger()
            ->send();
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->user->regenerateRecoveryCodes();

        Notification::make()
            ->title('New recovery codes generated')
            ->success()
            ->send();
    }
}
