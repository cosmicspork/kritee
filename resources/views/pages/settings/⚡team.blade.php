<?php

use App\Enums\UserRole;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new #[Title('Team')] class extends Component {
    use Toast;

    public string $email = '';
    public string $role = UserRole::Member->value;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Invitation>
     */
    #[Computed]
    public function pendingInvitations()
    {
        return Invitation::pending()->latest()->get();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function roleOptions(): array
    {
        return array_map(
            fn (UserRole $role) => ['id' => $role->value, 'name' => $role->label()],
            UserRole::cases(),
        );
    }

    /**
     * Send an invitation to the given email address.
     */
    public function invite(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $this->validate([
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Invitation::pending()->where('email', $value)->exists()) {
                        $fail(__('An invitation has already been sent to this address.'));
                    }
                },
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_by' => Auth::id(),
        ]);

        Mail::to($invitation->email)->queue(new InvitationMail($invitation));

        $this->reset('email', 'role');

        $this->success(__('Invitation sent to :email.', ['email' => $invitation->email]));
    }

    /**
     * Revoke a pending invitation.
     */
    public function revoke(int $invitation): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        Invitation::whereKey($invitation)->whereNull('accepted_at')->delete();

        unset($this->pendingInvitations);

        $this->success(__('Invitation revoked.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Team') }}</h2>

    <x-pages::settings.layout :heading="__('Team')" :subheading="__('Invite teammates and manage who has access')">
        <form wire:submit="invite" class="my-6 grid w-full gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <x-input
                wire:model="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                placeholder="teammate@example.com"
                data-test="invite-email"
            />

            <x-select
                wire:model="role"
                label="{{ __('Role') }}"
                :options="$this->roleOptions"
                data-test="invite-role"
            />

            <x-button type="submit" label="{{ __('Send invite') }}" class="btn-primary" data-test="send-invite-button" />
        </form>

        <div class="mt-8 space-y-2">
            <h3 class="text-sm font-medium text-base-content/70">{{ __('Members') }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->users as $member)
                        <tr wire:key="user-{{ $member->id }}">
                            <td>{{ $member->name }}</td>
                            <td class="text-base-content/70">{{ $member->email }}</td>
                            <td><x-badge :value="$member->role->label()" class="badge-soft" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($this->pendingInvitations->isNotEmpty())
            <div class="mt-8 space-y-2">
                <h3 class="text-sm font-medium text-base-content/70">{{ __('Pending invitations') }}</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Expires') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->pendingInvitations as $invitation)
                            <tr wire:key="invitation-{{ $invitation->id }}">
                                <td>{{ $invitation->email }}</td>
                                <td><x-badge :value="$invitation->role->label()" class="badge-soft" /></td>
                                <td class="text-base-content/70">{{ $invitation->expires_at->diffForHumans() }}</td>
                                <td class="text-end">
                                    <x-button
                                        label="{{ __('Revoke') }}"
                                        wire:click="revoke({{ $invitation->id }})"
                                        wire:confirm="{{ __('Revoke this invitation?') }}"
                                        class="btn-ghost btn-sm text-error"
                                        data-test="revoke-invite-{{ $invitation->id }}"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-pages::settings.layout>
</section>
