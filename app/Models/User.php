<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasAuditTrail;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasAuditTrail;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    use TwoFactorAuthenticatable;
    use MustVerifyEmailTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verification_code',
        'email_verification_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'operator']);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function enableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => encrypt(app('pragmarx.google2fa')->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(
                collect(range(1, 8))->map(fn () => Str::random(10))->all()
            )),
        ])->save();
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(
                collect(range(1, 8))->map(fn () => Str::random(10))->all()
            )),
        ])->save();
    }

    public function sendEmailVerificationNotification()
    {
        if (app()->environment('local', 'testing', 'development')) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->forceFill([
                'email_verification_code' => $code,
                'email_verification_code_expires_at' => now()->addHours(1),
            ])->save();

            logger("【DEV】 Email verification code for {$this->email}: {$code}");
        } else {
            $this->notify(new \App\Notifications\WelcomeOnboarding);
        }
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function verifiedContacts()
    {
        return $this->contacts()->whereNotNull('verified_at');
    }

    public function hasVerifiedContact(): bool
    {
        return $this->verifiedContacts()->exists();
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }
}
