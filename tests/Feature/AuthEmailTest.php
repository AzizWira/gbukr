<?php

namespace Tests\Feature;

use App\Mail\BrandedNotificationMail;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_notification(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Customer Email Test',
            'email' => 'verify@example.com',
            'password' => 'test1234',
            'password_confirmation' => 'test1234',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'verify@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_forgot_password_uses_reset_password_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'active' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
    public function test_auth_notifications_use_branded_mail_template(): void
    {
        $user = User::factory()->create(['email' => 'brand@example.com']);

        $verifyMail = (new VerifyEmailNotification)->toMail($user);
        $resetMail = (new ResetPasswordNotification('demo-token'))->toMail($user);

        $this->assertInstanceOf(BrandedNotificationMail::class, $verifyMail);
        $this->assertInstanceOf(BrandedNotificationMail::class, $resetMail);
        $this->assertSame('brand@example.com', $verifyMail->to[0]['address']);
        $this->assertSame('brand@example.com', $resetMail->to[0]['address']);
    }

}
