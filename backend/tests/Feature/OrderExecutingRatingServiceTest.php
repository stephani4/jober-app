<?php

namespace Tests\Feature;

use App\Enums\OrderExecutingStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderExecuting;
use App\Models\OrderExecutingRating;
use App\Models\OrderPoint;
use App\Models\User;
use App\Services\OrderExecutingRatingService;
use App\Services\OrderExecutingService;
use App\Services\OrderRpcService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderExecutingRatingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_author_can_rate_during_confirmation(): void
    {
        [$author, $executing] = $this->orderAwaitingConfirmation();

        $order = app(OrderExecutingRatingService::class)->rate($author, [
            'order_id' => $executing->order_id,
            'rating' => 4,
        ]);

        $this->assertSame(4, $order->currentExecuting?->ratingValue());
        $this->assertFalse($order->currentExecuting?->canAcceptRating());

        $row = OrderExecutingRating::query()->where('order_executing_id', $executing->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(4, $row->rating);
    }

    public function test_author_can_rate_completed_order_on_the_same_day(): void
    {
        [$author, $executing] = $this->orderAwaitingConfirmation();
        $service = app(OrderExecutingService::class);
        $service->confirm($executing->executor, [
            'order_id' => $executing->order_id,
            'code' => (string) $executing->confirmation_number,
        ]);

        $order = app(OrderExecutingRatingService::class)->rate($author, [
            'order_id' => $executing->order_id,
            'rating' => 5,
        ]);

        $this->assertSame(5, $order->currentExecuting?->ratingValue());
    }

    public function test_cannot_change_rating_after_it_is_saved(): void
    {
        [$author, $executing] = $this->orderAwaitingConfirmation();
        $service = app(OrderExecutingRatingService::class);
        $service->rate($author, [
            'order_id' => $executing->order_id,
            'rating' => 2,
        ]);

        $this->expectException(ValidationException::class);
        $service->rate($author, [
            'order_id' => $executing->order_id,
            'rating' => 5,
        ]);
    }

    public function test_cannot_rate_completed_order_on_the_next_day(): void
    {
        [$author, $executing] = $this->orderAwaitingConfirmation();
        app(OrderExecutingService::class)->confirm($executing->executor, [
            'order_id' => $executing->order_id,
            'code' => (string) $executing->confirmation_number,
        ]);

        Carbon::setTestNow(now()->addDay());

        $this->expectException(ValidationException::class);
        app(OrderExecutingRatingService::class)->rate($author, [
            'order_id' => $executing->order_id,
            'rating' => 3,
        ]);
    }

    public function test_executor_cannot_rate(): void
    {
        [, $executing] = $this->orderAwaitingConfirmation();

        $this->expectException(ValidationException::class);
        app(OrderExecutingRatingService::class)->rate($executing->executor, [
            'order_id' => $executing->order_id,
            'rating' => 5,
        ]);
    }

    public function test_rating_is_null_until_set(): void
    {
        [$author, $executing] = $this->orderAwaitingConfirmation();

        $payload = app(OrderRpcService::class)->watching($author, [
            'order_id' => $executing->order_id,
        ]);

        $this->assertNull($payload['rating']);
        $this->assertTrue($payload['can_rate']);
    }

    /**
     * @return array{0: User, 1: OrderExecuting}
     */
    private function orderAwaitingConfirmation(): array
    {
        $executor = User::factory()->create(['role' => UserRole::Executor]);
        $author = User::factory()->create(['role' => UserRole::Customer]);
        $order = Order::factory()->for($author)->create();
        OrderPoint::factory()->for($order)->create(['position' => 1, 'lat' => 55.75, 'lon' => 37.61]);
        OrderPoint::factory()->for($order)->create(['position' => 2, 'lat' => 55.74, 'lon' => 37.59]);
        $order->load('points');

        $service = app(OrderExecutingService::class);
        $started = $service->start($executor, ['order_id' => $order->id]);
        $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[0]->order_point_id,
        ]);
        $finished = $service->completePoint($executor, [
            'order_id' => $order->id,
            'order_point_id' => $started->points[1]->order_point_id,
        ]);

        $this->assertSame(OrderExecutingStatus::Confirmation, $finished->status);

        return [$author, $finished];
    }
}
