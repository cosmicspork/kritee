<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <x-input
                name="email"
                value="{{ request('email') }}"
                label="{{ __('Email') }}"
                type="email"
                required
                autocomplete="email"
                error-field="email"
            />

            <!-- Password -->
            <x-password
                name="password"
                label="{{ __('Password') }}"
                type="password"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                right
                error-field="password"
            />

            <!-- Confirm Password -->
            <x-password
                name="password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                right
                error-field="password_confirmation"
            />

            <x-button type="submit" label="{{ __('Reset password') }}" class="btn-primary w-full" data-test="reset-password-button" />
        </form>
    </div>
</x-layouts::auth>
