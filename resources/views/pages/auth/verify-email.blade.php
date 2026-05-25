<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-6">
        <p class="text-center text-sm text-base-content/70">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center text-sm font-medium text-success">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <x-button type="submit" label="{{ __('Resend verification email') }}" class="btn-primary w-full" />
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button type="submit" label="{{ __('Log out') }}" class="btn-ghost btn-sm" data-test="logout-button" />
            </form>
        </div>
    </div>
</x-layouts::auth>
