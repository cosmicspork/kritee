<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public bool $show = false;

    public string $password = '';

    /**
     * Open the account deletion confirmation modal.
     */
    #[On('open-delete-user-modal')]
    public function open(): void
    {
        $this->show = true;
    }

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-modal wire:model="show" box-class="max-w-lg" :title="__('Are you sure you want to delete your account?')" class="backdrop-blur">
    <p class="mb-4 text-base-content/70">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
    </p>

    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <x-password wire:model="password" label="{{ __('Password') }}" type="password" right />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <x-button label="{{ __('Cancel') }}" wire:click="$set('show', false)" />
            <x-button type="submit" label="{{ __('Delete account') }}" class="btn-error" data-test="confirm-delete-user-button" />
        </div>
    </form>
</x-modal>
