<x-filament::page heading="Two-Factor Authentication">
    <p class="mb-4">Enter the 6-digit code from your Authenticator app.</p>

    {{ $this->form }}

    <x-filament::button wire:click="submit" class="mt-2">Verify</x-filament::button>
</x-filament::page>
