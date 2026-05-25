<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                label="{{ __('Email address') }}"
                value="{{ old('email') }}"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                error-field="email"
            />

            <!-- Password -->
            <div>
                <x-password
                    name="password"
                    label="{{ __('Password') }}"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    right
                    error-field="password"
                />

                @if (Route::has('password.request'))
                    <div class="mt-1 text-end">
                        <a href="{{ route('password.request') }}" class="link link-hover text-sm" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Remember Me -->
            <x-checkbox name="remember" label="{{ __('Remember me') }}" value="1" @checked(old('remember')) />

            <x-button type="submit" label="{{ __('Log in') }}" class="btn-primary w-full" data-test="login-button" />
        </form>

        <div class="text-center text-sm text-base-content/60">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a href="{{ route('register') }}" class="link link-hover" wire:navigate>{{ __('Sign up') }}</a>
        </div>
    </div>
</x-layouts::auth>
