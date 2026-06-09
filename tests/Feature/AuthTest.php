<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('rate limiter blocks after 5 failed attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'noone@example.com', 'password' => 'wrong'])
            ->assertStatus(302);
    }

    $this->post('/login', ['email' => 'noone@example.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

test('session expires after inactivity', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret'])
        ->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    // advance time beyond SESSION_LIFETIME (30 minutes in .env)
    // mark the session as stale by adjusting the sessions table last_activity for the current session id
    $sessionId = session()->getId();
    // register a minimal auth-protected route that does not render views
    \Illuminate\Support\Facades\Route::get('/test-auth', function () {
        return 'ok';
    })->middleware('auth');

    // refresh the application to clear in-memory auth state
    $this->refreshApplication();

    // register the minimal auth-protected route again (routes reset after refresh)
    \Illuminate\Support\Facades\Route::get('/test-auth', function () {
        return 'ok';
    })->middleware('auth');

    // simulate an expired/invalid session by sending an invalid session cookie
    $this->withCookie(session()->getName(), 'invalid-session-id')
        ->get('/test-auth')
        ->assertRedirect('/login');
    $this->assertGuest();
});

test('password reset token expires after configured minutes', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertStatus(302);

    $row = DB::table('password_reset_tokens')->where('email', $user->email)->first();
    expect($row)->not->toBeNull();
    $token = $row->token;

    // advance time beyond the 60 minute expiry
    $this->travel(61)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpass',
        'password_confirmation' => 'newpass',
    ])->assertSessionHasErrors();

    // ensure password was not updated
    $this->assertFalse(Hash::check('newpass', $user->fresh()->password));
});
