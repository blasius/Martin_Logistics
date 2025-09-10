<x-filament::page>
    <div class="space-y-6">
        <h2 class="text-xl font-bold">Two-Factor Authentication</h2>

        @if ($user->two_factor_secret)
            <div class="space-y-4">
                <p class="text-green-600">✅ Two-factor authentication is enabled.</p>

                <div>
                    <h3 class="font-semibold">QR Code</h3>
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>

                <div>
                    <h3 class="font-semibold">Recovery Codes</h3>
                    <ul class="list-disc pl-6">
                        @foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex space-x-2">
                    <x-filament::button wire:click="regenerateRecoveryCodes" color="secondary">
                        Regenerate Recovery Codes
                    </x-filament::button>
                    <x-filament::button wire:click="disable2FA" color="danger">
                        Disable 2FA
                    </x-filament::button>
                </div>
            </div>
        @else
            <p class="text-red-600">⚠️ Two-factor authentication is disabled.</p>

            <x-filament::button wire:click="enable2FA" color="primary">
                Enable 2FA
            </x-filament::button>
        @endif
    </div>
</x-filament::page>
