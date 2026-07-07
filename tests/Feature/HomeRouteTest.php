<?php

use App\Models\User;

test('guests are redirected from the home page to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users reach the dashboard from the home page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/login');

    $this->get('/login')->assertRedirect(route('dashboard', absolute: false));
});
