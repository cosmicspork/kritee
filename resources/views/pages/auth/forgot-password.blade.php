<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
                error-field="email"
            />

            <x-button type="submit" label="{{ __('Email password reset link') }}" class="btn-primary w-full" data-test="email-password-reset-link-button" />
        </form>

        <div class="text-center text-sm text-base-content/60">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="link link-hover" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
