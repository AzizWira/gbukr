<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_link_has_no_effect_after_first_successful_use(): void
    {
        $user = User::factory()->unverified()->create();
        $user->customerProfile()->create([]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)
            ->withSession(['acting_as' => 'customer'])
            ->get($url)
            ->assertRedirect(route('customer.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)
            ->withSession(['acting_as' => 'customer'])
            ->get($url)
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHasErrors('email');
    }
}
