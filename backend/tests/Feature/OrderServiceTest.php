<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\OrderType;
use App\Models\User;
use App\Services\OrderExecutingService;
use App\Services\OrderRpcService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_create_order_attaches_temporary_files_to_points(): void
    {
        $user = User::factory()->create();
        $file = File::query()->create([
            'name' => 'brief.docx',
            'extension' => 'docx',
            'size' => 2048,
            'path' => 'attachments/brief.docx',
        ]);

        $this->assertNotNull($file->temporary_at);
        $this->assertNull($file->order_point_id);

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 1500,
            'points' => [[
                'description' => 'Забрать документы',
                'address' => 'Москва, Тверская 1',
                'lat' => 55.757,
                'lon' => 37.615,
                'file_ids' => [$file->id],
            ]],
        ]);

        $file->refresh();
        $this->assertNull($file->temporary_at);
        $this->assertSame($order->points[0]->id, $file->order_point_id);
        $this->assertCount(1, $order->points[0]->files);
        $this->assertSame('brief.docx', $order->points[0]->files[0]->name);
    }

    public function test_create_order_rejects_already_committed_file(): void
    {
        $user = User::factory()->create();
        $file = File::query()->create([
            'name' => 'used.pdf',
            'extension' => 'pdf',
            'size' => 100,
            'path' => 'attachments/used.pdf',
            'temporary_at' => null,
        ]);
        $file->forceFill(['temporary_at' => null])->save();

        $this->expectException(ValidationException::class);

        app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 1500,
            'points' => [[
                'description' => 'Забрать документы',
                'lat' => 55.757,
                'lon' => 37.615,
                'file_ids' => [$file->id],
            ]],
        ]);
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

    public function test_create_order_resolves_point_address_via_reverse_geocoding(): void
    {
        Http::fake([
            'maps.vk.com/api/search*' => Http::response([
                'results' => [
                    [
                        'name' => 'ЖК Весенний',
                        'address' => 'Кемеровская область, Кемерово, улица Весенняя, 1',
                        'type' => 'building',
                        'pin' => [86.08931, 55.35451],
                        'address_details' => [
                            'locality' => 'Кемерово',
                            'street' => 'улица Весенняя',
                            'building' => '1',
                        ],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 500,
            'points' => [[
                'description' => 'Забрать документы',
                'lat' => 55.35451,
                'lon' => 86.08931,
            ]],
        ]);

        $this->assertSame('Кемерово, улица Весенняя, 1', $order->points[0]->address);
    }

    public function test_create_order_replaces_coordinate_like_address_with_geocoded_one(): void
    {
        Http::fake([
            'maps.vk.com/api/search*' => Http::response([
                'results' => [
                    [
                        'address' => 'Кемеровская область, Кемерово, улица Весенняя, 1',
                        'type' => 'building',
                        'pin' => [86.08931, 55.35451],
                        'address_details' => [
                            'locality' => 'Кемерово',
                            'street' => 'улица Весенняя',
                            'building' => '1',
                        ],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 500,
            'points' => [[
                'description' => 'Забрать документы',
                'address' => '55.35451, 86.08931',
                'lat' => 55.35451,
                'lon' => 86.08931,
            ]],
        ]);

        $this->assertSame('Кемерово, улица Весенняя, 1', $order->points[0]->address);
    }

    public function test_create_order_keeps_address_null_when_geocoding_has_no_match(): void
    {
        // Ближайший объект — улица в соседнем квартале: в радиус 150 м не попадает.
        Http::fake([
            'maps.vk.com/api/search*' => Http::response([
                'results' => [
                    [
                        'address' => 'Кемеровская область, Кемерово, улица Весенняя',
                        'type' => 'street',
                        'pin' => [86.0793, 55.3545],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 500,
            'points' => [[
                'description' => 'Забрать документы',
                'lat' => 55.35451,
                'lon' => 86.08931,
            ]],
        ]);

        $this->assertNull($order->points[0]->address);
        Http::assertSentCount(1);
    }

    public function test_create_order_does_not_geocode_when_address_provided(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $order = app(OrderService::class)->create($user, [
            'order_type_id' => OrderType::ERRAND,
            'cost' => 500,
            'points' => [[
                'description' => 'Забрать документы',
                'address' => 'Кемерово, Весенняя 1',
                'lat' => 55.35451,
                'lon' => 86.08931,
            ]],
        ]);

        $this->assertSame('Кемерово, Весенняя 1', $order->points[0]->address);
        Http::assertNothingSent();
    }

    public function test_mine_includes_executor_profile_after_taken(): void
    {
        $author = User::factory()->create();
        $order = $this->orderFor($author, 'Доставка посылки');
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $file = File::create([
            'name' => 'avatar.png',
            'extension' => 'png',
            'size' => 1234,
            'path' => 'avatars/avatar.png',
        ]);
        $executor->update(['avatar_id' => $file->id]);

        app(OrderExecutingService::class)->start($executor, ['order_id' => $order->id]);

        $items = app(OrderRpcService::class)->mine($author);
        $found = collect($items)->firstWhere('id', $order->id);

        $this->assertNotNull($found);
        $this->assertSame($executor->name, $found['executor']['name']);
        $this->assertSame($file->id, $found['executor']['avatar_id']);
        $this->assertSame('/api/files/'.$file->id, $found['executor']['avatar_url']);
    }

    public function test_mine_hides_executor_after_decline(): void
    {
        $author = User::factory()->create();
        $order = $this->orderFor($author, 'Доставка посылки');
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $service = app(OrderExecutingService::class);

        $service->start($executor, ['order_id' => $order->id]);
        $service->decline($executor, ['order_id' => $order->id]);

        $items = app(OrderRpcService::class)->mine($author);
        $found = collect($items)->firstWhere('id', $order->id);

        $this->assertNotNull($found);
        $this->assertNull($found['executor']);
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
