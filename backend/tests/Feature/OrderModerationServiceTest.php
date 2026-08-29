<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Jobs\ModerateOrderJob;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\User;
use App\Moderation\OrderModerationRule;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use App\Services\OrderExecutingService;
use App\Services\OrderModerationService;
use App\Services\OrderRpcService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profanity_cancels_order_with_reason(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->never();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Нужно хуй сходить за хлебом');

        app(OrderModerationService::class)->process($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancel, $order->status);
        $this->assertSame('В заказе присутствует нецензурная лексика.', $order->reason);
    }

    public function test_profanity_in_point_description_cancels_order(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->never();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Обычное примечание');
        $order->points[0]->update(['description' => 'Нужно пиздец быстро']);

        app(OrderModerationService::class)->process($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancel, $order->status);
        $this->assertSame('В заказе присутствует нецензурная лексика.', $order->reason);
    }

    public function test_innocent_word_with_stem_substring_passes(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->once();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Нужно потреблять меньше сахара');

        app(OrderModerationService::class)->process($order->id);

        $this->assertSame(OrderStatus::Wait, $order->fresh()->status);
    }

    public function test_custom_rule_can_cancel_with_own_reason(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->never();
        });

        $rule = new class implements OrderModerationRule
        {
            public function evaluate(Order $order): ?string
            {
                return 'Нарушено дополнительное правило модерации.';
            }
        };

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Чистое описание');

        $service = new OrderModerationService(
            [$rule],
            app(CentrifugoClient::class),
            app(CentrifugoTokenService::class),
        );
        $service->process($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Cancel, $order->status);
        $this->assertSame('Нарушено дополнительное правило модерации.', $order->reason);
    }

    public function test_moderate_order_cannot_be_started(): void
    {
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $order = $this->orderFor($author, 'Ещё на модерации');

        $this->expectException(ValidationException::class);
        app(OrderExecutingService::class)->start($executor, ['order_id' => $order->id]);
    }

    public function test_create_dispatches_moderation_job(): void
    {
        Queue::fake();
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->once();
            $mock->shouldReceive('broadcast')->never();
        });

        $user = User::factory()->create(['role' => UserRole::Customer]);
        app(OrderRpcService::class)->create($user, [
            'description' => 'Срочно',
            'cost' => 1000,
            'points' => [[
                'description' => 'Забрать документы',
                'lat' => 55.757,
                'lon' => 37.615,
            ]],
        ]);

        Queue::assertPushed(ModerateOrderJob::class, function (ModerateOrderJob $job) {
            return $job->orderId > 0;
        });
    }

    public function test_clean_order_becomes_wait(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->once();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Забрать документы у секретаря');

        app(OrderModerationService::class)->process($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Wait, $order->status);
        $this->assertNull($order->reason);
    }

    public function test_history_includes_cancelled_orders(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->zeroOrMoreTimes();
            $mock->shouldReceive('broadcast')->zeroOrMoreTimes();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Это пиздец как срочно');
        app(OrderModerationService::class)->process($order->id);

        $history = app(OrderService::class)->listHistory($author, []);
        $this->assertTrue($history['items']->contains('id', $order->id));
        $this->assertSame(OrderStatus::Cancel, $history['items']->first()->status);
    }

    public function test_job_moderates_order(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
            $mock->shouldReceive('broadcast')->once();
        });

        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author, 'Доставить пакет');

        (new ModerateOrderJob($order->id))->handle(app(OrderModerationService::class));

        $this->assertSame(OrderStatus::Wait, $order->fresh()->status);
    }

    private function orderFor(User $author, string $description): Order
    {
        $order = Order::factory()->for($author)->create([
            'description' => $description,
            'status' => OrderStatus::Moderate,
        ]);

        OrderPoint::factory()->for($order)->create([
            'position' => 1,
            'description' => 'Забрать у ресепшен',
            'lat' => 55.757,
            'lon' => 37.615,
        ]);

        return $order->load('points');
    }
}
