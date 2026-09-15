<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\OrderType;
use App\Models\User;
use App\Services\OrderExecutingService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_with_points_and_cost(): void
    {
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
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
        $this->assertSame(OrderType::ERRAND, $order->order_type_id);
        $this->assertSame('Срочно', $order->description);
        $this->assertEquals(2500, (float) $order->cost);
        $this->assertCount(2, $order->points);
        $this->assertSame(1, $order->points[0]->position);
        $this->assertSame(2, $order->points[1]->position);
        $this->assertSame('moderate', $order->status->value);
        $this->assertNull($order->points[0]->entrance);
        $this->assertNull($order->points[0]->floor);
        $this->assertNull($order->points[0]->apartment);
        $this->assertNull($order->points[0]->intercom);
    }

    public function test_create_order_saves_building_access_fields(): void
    {
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::BUY_AND_DELIVER,
            'description' => 'Привезти в офис',
            'cost' => 900,
            'points' => [[
                'description' => 'Коробка с документами',
                'address' => 'Кемерово, Весенняя 1',
                'lat' => 55.3545,
                'lon' => 86.0893,
                'entrance' => 2,
                'floor' => 5,
                'apartment' => '12',
                'intercom' => 1234,
            ]],
        ]);

        $point = $order->points->first();
        $this->assertSame(2, (int) $point->entrance);
        $this->assertSame(5, (int) $point->floor);
        $this->assertSame('12', $point->apartment);
        $this->assertSame(1234, (int) $point->intercom);
    }

    public function test_buy_and_deliver_accepts_single_point(): void
    {
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::BUY_AND_DELIVER,
            'cost' => 800,
            'points' => [[
                'description' => 'Молоко 2л и хлеб',
                'address' => 'Кемерово, Ленина 1',
                'lat' => 55.3545,
                'lon' => 86.0893,
            ]],
        ]);

        $this->assertSame(OrderType::BUY_AND_DELIVER, $order->order_type_id);
        $this->assertCount(1, $order->points);
    }

    public function test_buy_and_deliver_rejects_multiple_points(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::BUY_AND_DELIVER,
            'cost' => 800,
            'points' => [
                [
                    'description' => 'Купить молоко',
                    'lat' => 55.3545,
                    'lon' => 86.0893,
                ],
                [
                    'description' => 'Вторая точка',
                    'lat' => 55.36,
                    'lon' => 86.09,
                ],
            ],
        ]);
    }

    public function test_create_requires_order_type(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(OrderService::class)->create($user, [
            'cost' => 800,
            'points' => [[
                'description' => 'Забрать документы',
                'lat' => 55.757,
                'lon' => 37.615,
            ]],
        ]);
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
        $finished = $executing->completePoint($executor, [
            'order_id' => $done->id,
            'order_point_id' => $startedDone->points[0]->order_point_id,
        ]);
        $executing->confirm($executor, [
            'order_id' => $done->id,
            'code' => (string) $finished->confirmation_number,
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
            $finished = $executing->completePoint($executor, [
                'order_id' => $order->id,
                'order_point_id' => $started->points[0]->order_point_id,
            ]);
            $executing->confirm($executor, [
                'order_id' => $order->id,
                'code' => (string) $finished->confirmation_number,
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
