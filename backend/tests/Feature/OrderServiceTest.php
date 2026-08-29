<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\User;
use App\Services\OrderExecutingService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_with_points_and_cost(): void
    {
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'description' => 'Срочно',
            'cost' => 2500,
            'points' => [
                [
                    'description' => 'Забрать документы',
                    'address' => 'Москва, Тверская 1',
                    'lat' => 55.757,
                    'lon' => 37.615,
                ],
                [
                    'description' => 'Отвезти клиенту',
                    'address' => 'Москва, Арбат 10',
                    'lat' => 55.749,
                    'lon' => 37.591,
                ],
            ],
        ]);

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Срочно', $order->description);
        $this->assertEquals(2500, (float) $order->cost);
        $this->assertCount(2, $order->points);
        $this->assertSame(1, $order->points[0]->position);
        $this->assertSame(2, $order->points[1]->position);
        $this->assertSame('moderate', $order->status->value);
    }

    public function test_feed_excludes_orders_with_executor(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $executor = User::factory()->create(['role' => UserRole::Executor]);

        $open = $this->orderFor($customer, 'Свободный');
        $taken = $this->orderFor($customer, 'Тест тест тест');
        $pending = $this->orderFor($customer, 'На модерации', OrderStatus::Moderate);

        app(OrderExecutingService::class)->start($executor, ['order_id' => $taken->id]);

        $feed = app(OrderService::class)->listFeed();

        $this->assertTrue($feed->contains('id', $open->id));
        $this->assertFalse($feed->contains('id', $taken->id));
        $this->assertFalse($feed->contains('id', $pending->id));
        $this->assertSame('wait', $open->fresh()->status->value);
        $this->assertSame('process', $taken->fresh()->status->value);
    }

    public function test_list_mine_returns_moderate_wait_and_process_orders(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $waiting = $this->orderFor($customer, 'Ждёт');
        $inProcess = $this->orderFor($customer, 'В работе');
        $done = $this->orderFor($customer, 'Готово');
        $pending = $this->orderFor($customer, 'Проверяем', OrderStatus::Moderate);

        $executing = app(OrderExecutingService::class);
        $executing->start($executor, ['order_id' => $inProcess->id]);
        $startedDone = $executing->start($executor, ['order_id' => $done->id]);
        $executing->completePoint($executor, [
            'order_id' => $done->id,
            'order_point_id' => $startedDone->points[0]->order_point_id,
        ]);

        $mine = app(OrderService::class)->listMine($customer);

        $this->assertTrue($mine->contains('id', $waiting->id));
        $this->assertTrue($mine->contains('id', $inProcess->id));
        $this->assertTrue($mine->contains('id', $pending->id));
        $this->assertFalse($mine->contains('id', $done->id));
    }

    public function test_list_history_paginates_completed_orders(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $service = app(OrderService::class);
        $executing = app(OrderExecutingService::class);

        for ($index = 0; $index < 16; $index++) {
            $order = $this->orderFor($customer, "История {$index}");
            $started = $executing->start($executor, ['order_id' => $order->id]);
            $executing->completePoint($executor, [
                'order_id' => $order->id,
                'order_point_id' => $started->points[0]->order_point_id,
            ]);
        }

        $this->orderFor($customer, 'Ещё ждёт');

        $firstPage = $service->listHistory($customer, []);
        $this->assertCount(15, $firstPage['items']);
        $this->assertNotNull($firstPage['next_cursor']);

        $secondPage = $service->listHistory($customer, [
            'cursor' => $firstPage['next_cursor'],
        ]);
        $this->assertCount(1, $secondPage['items']);
        $this->assertNull($secondPage['next_cursor']);
        $this->assertTrue($secondPage['items']->every(
            fn ($order) => in_array($order->status, [OrderStatus::Complete, OrderStatus::Cancel], true),
        ));
    }

    private function orderFor(User $author, string $description, OrderStatus $status = OrderStatus::Wait): Order
    {
        $order = Order::factory()->for($author)->create([
            'description' => $description,
            'status' => $status,
        ]);

        OrderPoint::factory()->for($order)->create([
            'position' => 1,
            'lat' => 55.757,
            'lon' => 37.615,
        ]);

        return $order;
    }
}
