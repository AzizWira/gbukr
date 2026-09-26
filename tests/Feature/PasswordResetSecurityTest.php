<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_form_does_not_trust_client_email_and_token_is_single_use(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-owner@example.com',
            'password' => 'OldPassword123',
        ]);
        $other = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => 'VictimPassword123',
        ]);

        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee($user->email)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="token"', false);

        $response = $this->post(route('password.update'), [
            'email' => $other->email,
            'token' => 'manipulated-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertTrue(Hash::check('VictimPassword123', $other->fresh()->password));

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }
}
