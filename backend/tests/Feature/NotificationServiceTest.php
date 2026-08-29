<?php

namespace Tests\Feature;

use App\Enums\OrderExecutingStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\NotificationService;
use App\Services\OrderExecutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_taken_notifies_author_with_executor_name(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->once();
        });

        [$executor, $order] = $this->executorAndOrder();
        $started = app(OrderExecutingService::class)->start($executor, ['order_id' => $order->id]);

        app(NotificationService::class)->notifyOrderTaken($started);

        $notification = $order->user->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Заказ взят в работу', $notification->data['title']);
        $this->assertSame($executor->id, $notification->data['executor_id']);
        $this->assertSame($executor->name, $notification->data['executor_name']);
        $this->assertStringContainsString($executor->name, $notification->data['body']);
        $this->assertSame(0, $executor->notifications()->count());
    }

    public function test_order_completed_notifies_author_and_executor(): void
    {
        $this->mock(CentrifugoClient::class, function ($mock) {
            $mock->shouldReceive('publish')->twice();
        });

        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $started = $service->start($executor, ['order_id' => $order->id]);

        foreach ($started->points as $point) {
            $started = $service->completePoint($executor, [
                'order_id' => $order->id,
                'order_point_id' => $point->order_point_id,
            ]);
        }

        $this->assertSame(OrderExecutingStatus::Complete, $started->status);

        app(NotificationService::class)->notifyOrderCompleted($started);

        $this->assertSame(1, $order->user->notifications()->count());
        $this->assertSame(1, $executor->notifications()->count());
        $this->assertSame('Заказ выполнен', $order->user->notifications()->first()?->data['title']);
        $this->assertSame('Заказ выполнен', $executor->notifications()->first()?->data['title']);
    }

    public function test_list_paginates_and_filters_unread(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user);
        $executor = User::factory()->create(['role' => UserRole::Executor]);

        for ($index = 0; $index < 16; $index++) {
            $user->notify(new OrderCompletedNotification($order, $executor, 'author'));
        }

        $user->notifications()->orderBy('created_at')->orderBy('id')->first()?->markAsRead();

        $service = app(NotificationService::class);
        $firstPage = $service->list($user, ['filter' => 'all']);

        $this->assertCount(15, $firstPage['items']);
        $this->assertNotNull($firstPage['next_cursor']);
        $this->assertSame(15, $firstPage['unread_count']);

        $secondPage = $service->list($user, [
            'filter' => 'all',
            'cursor' => $firstPage['next_cursor'],
        ]);

        $this->assertCount(1, $secondPage['items']);
        $this->assertNull($secondPage['next_cursor']);

        $unread = $service->list($user, ['filter' => 'unread']);
        $this->assertCount(15, $unread['items']);
        $this->assertSame(15, $unread['unread_count']);
    }

    public function test_mark_read_updates_unread_count(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user);
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $user->notify(new OrderCompletedNotification($order, $executor, 'author'));
        $user->notify(new OrderCompletedNotification($order, $executor, 'author'));

        $ids = $user->notifications()->pluck('id')->all();
        $result = app(NotificationService::class)->markRead($user, ['ids' => $ids]);

        $this->assertSame(0, $result['unread_count']);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function executorAndOrder(): array
    {
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        return [$executor, $this->orderFor($customer)];
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
