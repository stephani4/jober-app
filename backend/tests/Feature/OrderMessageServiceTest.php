<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderPoint;
use App\Models\User;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\OrderExecutingService;
use App\Services\OrderMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_executor_and_author_can_send_and_list_messages(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('broadcast')->twice();
        });

        [$executor, $order] = $this->startedOrder();
        $service = app(OrderMessageService::class);

        $fromExecutor = $service->send($executor, [
            'order_id' => $order->id,
            'body' => 'Уже выехал',
        ]);
        $fromAuthor = $service->send($order->user, [
            'order_id' => $order->id,
            'body' => 'Жду у входа',
        ]);

        $this->assertSame('Уже выехал', $fromExecutor->body);
        $this->assertSame($executor->id, $fromExecutor->user_id);

        $list = $service->list($order->user, ['order_id' => $order->id]);
        $this->assertCount(2, $list);
        $this->assertSame($fromExecutor->id, $list[0]->id);
        $this->assertSame($fromAuthor->id, $list[1]->id);
        $this->assertSame(2, OrderMessage::query()->where('order_id', $order->id)->count());
    }

    public function test_stranger_cannot_list_messages(): void
    {
        $this->mock(CentrifugoClient::class);
        [, $order] = $this->startedOrder();
        $stranger = User::factory()->create(['role' => UserRole::Customer]);

        $this->expectException(ValidationException::class);
        app(OrderMessageService::class)->list($stranger, ['order_id' => $order->id]);
    }

    public function test_stranger_cannot_send_messages(): void
    {
        $this->mock(CentrifugoClient::class);
        [, $order] = $this->startedOrder();
        $stranger = User::factory()->create(['role' => UserRole::Customer]);

        $this->expectException(ValidationException::class);
        app(OrderMessageService::class)->send($stranger, [
            'order_id' => $order->id,
            'body' => 'Привет',
        ]);
    }

    public function test_cannot_chat_until_order_is_taken(): void
    {
        $this->mock(CentrifugoClient::class);
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($author);

        $this->expectException(ValidationException::class);
        app(OrderMessageService::class)->send($author, [
            'order_id' => $order->id,
            'body' => 'Есть кто?',
        ]);
    }

    public function test_cannot_send_after_order_is_complete(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('broadcast')->zeroOrMoreTimes();
        });

        [$executor, $order] = $this->startedOrder();
        $executing = app(OrderExecutingService::class);
        $started = $executing->start($executor, ['order_id' => $order->id]);
        foreach ($started->points as $point) {
            $executing->completePoint($executor, [
                'order_id' => $order->id,
                'order_point_id' => $point->order_point_id,
            ]);
        }

        $this->assertSame(OrderStatus::Complete, $order->fresh()->status);

        $this->expectException(ValidationException::class);
        app(OrderMessageService::class)->send($executor, [
            'order_id' => $order->id,
            'body' => 'Ещё вопрос',
        ]);
    }

    public function test_empty_body_is_rejected(): void
    {
        $this->mock(CentrifugoClient::class);
        [$executor, $order] = $this->startedOrder();

        $this->expectException(ValidationException::class);
        app(OrderMessageService::class)->send($executor, [
            'order_id' => $order->id,
            'body' => '   ',
        ]);
    }

    public function test_http_routes_list_and_send_messages(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('broadcast')->once();
        });

        [$executor, $order] = $this->startedOrder();

        $send = $this->actingAs($executor, 'api')->postJson("/api/orders/{$order->id}/messages", [
            'body' => 'На месте',
        ]);
        $send->assertCreated()
            ->assertJsonPath('data.body', 'На месте')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.user_id', $executor->id);

        $list = $this->actingAs($executor, 'api')->getJson("/api/orders/{$order->id}/messages");
        $list->assertOk()->assertJsonCount(1, 'data');
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function startedOrder(): array
    {
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor($customer);
        app(OrderExecutingService::class)->start($executor, ['order_id' => $order->id]);

        return [$executor, $order->fresh(['user', 'currentExecuting.executor'])];
    }

    private function orderFor(User $author): Order
    {
        $order = Order::factory()->for($author)->create(['description' => 'Документы']);

        OrderPoint::factory()->for($order)->create([
            'position' => 1,
            'lat' => 55.757,
            'lon' => 37.615,
        ]);
        OrderPoint::factory()->for($order)->create([
            'position' => 2,
            'lat' => 55.749,
            'lon' => 37.591,
        ]);

        return $order->load('points', 'user');
    }
}
