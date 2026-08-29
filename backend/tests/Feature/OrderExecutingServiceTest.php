<?php

namespace Tests\Feature;

use App\Enums\OrderExecutingStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderPoint;
use App\Models\User;
use App\Services\OrderExecutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderExecutingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_sets_process_and_opens_first_point(): void
    {
        [$executor, $order] = $this->executorAndOrder();

        $executing = app(OrderExecutingService::class)->start($executor, [
            'order_id' => $order->id,
        ]);

        $this->assertSame(OrderExecutingStatus::Process, $executing->status);
        $this->assertNotNull($executing->process_at);
        $this->assertNull($executing->complete_at);
        $this->assertCount(2, $executing->points);
        $this->assertSame(OrderExecutingStatus::Process, $executing->points[0]->status);
        $this->assertNotNull($executing->points[0]->process_at);
        $this->assertSame(OrderExecutingStatus::Wait, $executing->points[1]->status);
        $this->assertNull($executing->points[1]->process_at);
    }

    public function test_start_is_idempotent_while_in_process(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);

        $first = $service->start($executor, ['order_id' => $order->id]);
        $second = $service->start($executor, ['order_id' => $order->id]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $executor->orderExecutings()->count());
    }

    public function test_complete_point_advances_to_next_and_finishes_on_last(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);

        $started = $service->start($executor, ['order_id' => $order->id]);
        $firstPointId = $started->points[0]->order_point_id;
        $secondPointId = $started->points[1]->order_point_id;

        $afterFirst = $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $firstPointId,
        ]);

        $this->assertSame(OrderExecutingStatus::Process, $afterFirst->status);
        $this->assertSame(OrderExecutingStatus::Complete, $afterFirst->points[0]->status);
        $this->assertNotNull($afterFirst->points[0]->complete_at);
        $this->assertSame(OrderExecutingStatus::Process, $afterFirst->points[1]->status);
        $this->assertNotNull($afterFirst->points[1]->process_at);

        $finished = $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $secondPointId,
        ]);

        $this->assertSame(OrderExecutingStatus::Complete, $finished->status);
        $this->assertNotNull($finished->complete_at);
        $this->assertSame(OrderExecutingStatus::Complete, $finished->points[1]->status);
        $this->assertNotNull($finished->points[1]->complete_at);
    }

    public function test_customer_cannot_start_execution(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $order = $this->orderFor(User::factory()->create(['role' => UserRole::Customer]));

        $this->expectException(ValidationException::class);

        app(OrderExecutingService::class)->start($customer, ['order_id' => $order->id]);
    }

    public function test_cannot_start_own_order(): void
    {
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $order = $this->orderFor($executor);

        $this->expectException(ValidationException::class);

        app(OrderExecutingService::class)->start($executor, ['order_id' => $order->id]);
    }

    public function test_second_executor_cannot_start_taken_order(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $other = User::factory()->create(['role' => UserRole::Executor]);
        $service = app(OrderExecutingService::class);

        $service->start($executor, ['order_id' => $order->id]);

        $this->expectException(ValidationException::class);

        $service->start($other, ['order_id' => $order->id]);
    }

    public function test_author_can_watch_process_order(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $service->start($executor, ['order_id' => $order->id]);

        $watching = $service->watching($order->user, ['order_id' => $order->id]);

        $this->assertSame($order->id, $watching->order_id);
        $this->assertSame(OrderExecutingStatus::Process, $watching->status);
    }

    public function test_non_author_cannot_watch(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $service->start($executor, ['order_id' => $order->id]);

        $this->expectException(ValidationException::class);

        $service->watching($executor, ['order_id' => $order->id]);
    }

    public function test_cannot_watch_until_taken(): void
    {
        [, $order] = $this->executorAndOrder();

        $this->expectException(ValidationException::class);

        app(OrderExecutingService::class)->watching($order->user, ['order_id' => $order->id]);
    }

    public function test_cannot_watch_completed_order(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $started = $service->start($executor, ['order_id' => $order->id]);

        $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[0]->order_point_id,
        ]);
        $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[1]->order_point_id,
        ]);

        $this->expectException(ValidationException::class);

        $service->watching($order->user, ['order_id' => $order->id]);
    }

    public function test_executor_location_is_stored(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $service->start($executor, ['order_id' => $order->id]);

        $updated = $service->updateLocation($executor, [
            'order_id' => $order->id,
            'lat' => 55.76,
            'lon' => 37.62,
        ]);

        $this->assertEqualsWithDelta(55.76, (float) $updated->lat, 0.0001);
        $this->assertEqualsWithDelta(37.62, (float) $updated->lon, 0.0001);
        $this->assertNotNull($updated->location_at);
    }

    public function test_active_returns_watch_for_author_and_execute_for_executor(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $service->start($executor, ['order_id' => $order->id]);

        $this->assertSame(
            ['order_id' => $order->id, 'view' => 'execute'],
            $service->active($executor),
        );
        $this->assertSame(
            ['order_id' => $order->id, 'view' => 'watch'],
            $service->active($order->user),
        );
        $this->assertNull($service->active(User::factory()->create(['role' => UserRole::Customer])));
    }

    public function test_active_is_empty_when_execution_completed(): void
    {
        [$executor, $order] = $this->executorAndOrder();
        $service = app(OrderExecutingService::class);
        $started = $service->start($executor, ['order_id' => $order->id]);
        $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[0]->order_point_id,
        ]);
        $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[1]->order_point_id,
        ]);

        $this->assertNull($service->active($executor));
        $this->assertNull($service->active($order->user));
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
        $order = Order::factory()->for($author)->create();

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

        return $order->load('points');
    }
}
