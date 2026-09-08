<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WebPushServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_vapid_key(): void
    {
        $this->getJson('/api/push/vapid')->assertUnauthorized();
    }

    public function test_user_receives_vapid_public_key_when_configured(): void
    {
        config([
            'webpush.vapid.public_key' => 'test-public',
            'webpush.vapid.private_key' => 'test-private',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/push/vapid')
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'public_key' => 'test-public',
            ]);
    }

    public function test_user_can_save_and_replace_push_subscription(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'api')
            ->postJson('/api/push/subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
                'keys' => [
                    'p256dh' => 'public-key-a',
                    'auth' => 'auth-token-a',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
            'public_key' => 'public-key-a',
        ]);

        $this->actingAs($other, 'api')
            ->postJson('/api/push/subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
                'keys' => [
                    'p256dh' => 'public-key-b',
                    'auth' => 'auth-token-b',
                ],
            ])
            ->assertOk();

        $this->assertSame(1, PushSubscription::query()->count());
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $other->id,
            'public_key' => 'public-key-b',
        ]);
    }

    public function test_user_can_unsubscribe_own_endpoint(): void
    {
        $user = User::factory()->create();
        app(WebPushService::class)->subscribe($user, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
            'keys' => [
                'p256dh' => 'public-key-a',
                'auth' => 'auth-token-a',
            ],
        ]);

        $this->actingAs($user, 'api')
            ->deleteJson('/api/push/subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
            ])
            ->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_subscribe_requires_keys(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(WebPushService::class)->subscribe($user, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-a',
        ]);
    }
}
