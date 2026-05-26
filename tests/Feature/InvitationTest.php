<?php

use App\Enums\UserRole;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('admins can open the team page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('team.edit'))
        ->assertOk();
});

test('members cannot reach the team page', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('team.edit'))
        ->assertForbidden();
});

test('an admin can invite a teammate', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::settings.team')
        ->set('email', 'teammate@example.com')
        ->set('role', UserRole::Member->value)
        ->call('invite')
        ->assertHasNoErrors();

    $invitation = Invitation::where('email', 'teammate@example.com')->first();

    expect($invitation)->not->toBeNull();
    expect($invitation->role)->toBe(UserRole::Member);
    expect($invitation->invited_by)->toBe($admin->id);
    expect($invitation->isAcceptable())->toBeTrue();

    Mail::assertQueued(InvitationMail::class, fn (InvitationMail $mail) => $mail->hasTo('teammate@example.com'));
});

test('an admin can invite another admin', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::settings.team')
        ->set('email', 'second-admin@example.com')
        ->set('role', UserRole::Admin->value)
        ->call('invite')
        ->assertHasNoErrors();

    expect(Invitation::where('email', 'second-admin@example.com')->first()->role)
        ->toBe(UserRole::Admin);
});

test('inviting an existing user is rejected', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::settings.team')
        ->set('email', $existing->email)
        ->call('invite')
        ->assertHasErrors('email');

    Mail::assertNothingQueued();
});

test('inviting an already-pending email is rejected', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    Invitation::factory()->create(['email' => 'pending@example.com']);

    Livewire::actingAs($admin)
        ->test('pages::settings.team')
        ->set('email', 'pending@example.com')
        ->call('invite')
        ->assertHasErrors('email');

    Mail::assertNothingQueued();
});

test('an admin can revoke a pending invitation', function () {
    $admin = User::factory()->admin()->create();
    $invitation = Invitation::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::settings.team')
        ->call('revoke', $invitation->id);

    expect(Invitation::find($invitation->id))->toBeNull();
});

test('a valid invitation renders the acceptance form', function () {
    $invitation = Invitation::factory()->create(['email' => 'invitee@example.com']);

    $this->get(route('invitations.accept', $invitation->token))
        ->assertOk()
        ->assertSee('invitee@example.com');
});

test('accepting an invitation creates the user with the invited role and logs them in', function () {
    $invitation = Invitation::factory()->admin()->create(['email' => 'invitee@example.com']);

    Livewire::test('pages::invitations.accept', ['token' => $invitation->token])
        ->set('name', 'New Teammate')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'invitee@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('New Teammate');
    expect($user->role)->toBe(UserRole::Admin);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('an expired invitation cannot be accepted', function () {
    $invitation = Invitation::factory()->expired()->create();

    Livewire::test('pages::invitations.accept', ['token' => $invitation->token])
        ->assertSet('acceptable', false);
});

test('an already-accepted invitation cannot be accepted', function () {
    $invitation = Invitation::factory()->accepted()->create();

    Livewire::test('pages::invitations.accept', ['token' => $invitation->token])
        ->assertSet('acceptable', false);
});

test('an unknown invitation token returns 404', function () {
    $this->get(route('invitations.accept', 'does-not-exist'))
        ->assertNotFound();
});

test('public registration is disabled by default', function () {
    $this->get('/register')->assertNotFound();
});
