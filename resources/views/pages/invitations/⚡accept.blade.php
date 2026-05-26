<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Accept invitation')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    #[Locked]
    public Invitation $invitation;

    public bool $acceptable = false;

    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Resolve the invitation from its token.
     */
    public function mount(string $token): void
    {
        $this->invitation = Invitation::where('token', $token)->firstOrFail();
        $this->acceptable = $this->invitation->isAcceptable();
    }

    /**
     * Accept the invitation: create the account and sign in.
     */
    public function accept(): void
    {
        abort_unless($this->invitation->isAcceptable(), 403);

        $validated = $this->validate([
            'name' => $this->nameRules(),
            'password' => $this->passwordRules(),
        ]);

        if (User::where('email', $this->invitation->email)->exists()) {
            $this->addError('email', __('An account already exists for this email address.'));

            return;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $this->invitation->email,
            'password' => $validated['password'],
            'role' => $this->invitation->role,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->invitation->forceFill(['accepted_at' => now()])->save();

        Auth::login($user);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if ($acceptable)
        <div class="flex flex-col gap-6">
            <x-auth-header
                :title="__('Accept your invitation')"
                :description="__('Set up your account to join :app', ['app' => config('app.name')])"
            />

            <form wire:submit="accept" class="flex flex-col gap-6">
                <x-input
                    label="{{ __('Email address') }}"
                    value="{{ $invitation->email }}"
                    type="email"
                    readonly
                    data-test="invite-accept-email"
                />

                <x-input
                    wire:model="name"
                    label="{{ __('Name') }}"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="{{ __('Full name') }}"
                />

                <x-password
                    wire:model="password"
                    label="{{ __('Password') }}"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('Password') }}"
                    right
                />

                <x-password
                    wire:model="password_confirmation"
                    label="{{ __('Confirm password') }}"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('Confirm password') }}"
                    right
                />

                <x-button type="submit" label="{{ __('Create account') }}" class="btn-primary w-full" data-test="accept-invite-button" />
            </form>
        </div>
    @else
        <div class="flex flex-col gap-4 text-center">
            <x-auth-header
                :title="__('Invitation unavailable')"
                :description="__('This invitation has expired or has already been used.')"
            />

            <a href="{{ route('login') }}" class="link link-hover text-sm" wire:navigate>{{ __('Return to log in') }}</a>
        </div>
    @endif
</div>
