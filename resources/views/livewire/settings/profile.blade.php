<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <div class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            <flux:input wire:model="phone_number" :label="__('Phone Number')" type="tel" autocomplete="tel" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                <div>
                    <flux:text class="mt-4">
                        {{ __('Your email address is unverified.') }}

                        <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end w-full">
                    <flux:button variant="primary" wire:click="startConfirmingProfileUpdate" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </div>

        <!-- Password Confirmation Modal -->
        <flux:modal name="confirm-profile-update" :show="$confirmingProfileUpdate" focusable class="max-w-lg">
            <form wire:submit="confirmProfileUpdate" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Confirm Password') }}</flux:heading>

                    <flux:subheading>
                        {{ __('For your security, please confirm your password before updating your profile information.') }}
                    </flux:subheading>
                </div>

                <flux:input wire:model="password" :label="__('Password')" type="password" autofocus />

                <x-input-error for="password" class="mt-2" />

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:button variant="filled" wire:click="stopConfirmingProfileUpdate">{{ __('Cancel') }}</flux:button>

                    <flux:button variant="primary" type="submit">{{ __('Confirm') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
