<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderExecuting;
use App\Models\OrderExecutingRating;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Оценка автором качества выполнения заказа (1–5).
 */
class OrderExecutingRatingService
{
    /**
     * Сохраняет оценку один раз, пока открыто окно: confirmation или текущие сутки после завершения.
     *
     * @param  array<string, mixed>  $data
     */
    public function rate(User $author, array $data): Order
    {
        $payload = $this->validate($data);
        $order = Order::query()->findOrFail($payload['order_id']);

        if ($order->user_id !== $author->id) {
            throw ValidationException::withMessages([
                'order_id' => 'Оценить выполнение может только автор заказа.',
            ]);
        }

        $executing = OrderExecuting::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if (! $executing) {
            throw ValidationException::withMessages([
                'order_id' => 'Заказ ещё не выполнялся.',
            ]);
        }

        if (! $executing->canAcceptRating()) {
            throw ValidationException::withMessages([
                'rating' => $executing->ratingValue() !== null
                    ? 'Оценка уже сохранена и её нельзя изменить.'
                    : 'Оценить можно на подтверждении или в течение текущих суток после выполнения.',
            ]);
        }

        OrderExecutingRating::query()->create([
            'order_executing_id' => $executing->id,
            'rating' => $payload['rating'],
        ]);

        return $order->fresh(['points.files', 'user', 'currentExecuting.executor.avatar', 'currentExecuting.rating', 'orderType']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int, rating: int}
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'order_id.exists' => 'Заказ не найден.',
            'rating.required' => 'Укажите оценку.',
            'rating.min' => 'Оценка от 1 до 5.',
            'rating.max' => 'Оценка от 1 до 5.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int, rating: int} $validated */
        $validated = $validator->validated();

        return $validated;
    }
}
