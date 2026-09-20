<?php

namespace App\Services;

use App\Enums\OrderExecutingStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderExecuting;
use App\Models\OrderExecutingPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Взятие заказа в работу и продвижение по точкам маршрута.
 */
class OrderExecutingService
{
    /**
     * Начинает выполнение заказа (status=process) или возвращает уже начатое,
     * включая выполнения на этапе подтверждения кодом.
     *
     * @param  array<string, mixed>  $data
     */
    public function start(User $executor, array $data): OrderExecuting
    {
        $this->assertExecutor($executor);

        $payload = $this->validateIds($data);
        $order = Order::query()->with('points')->findOrFail($payload['order_id']);

        if ($order->user_id === $executor->id) {
            throw ValidationException::withMessages([
                'order_id' => 'Нельзя взять в работу собственный заказ.',
            ]);
        }

        if ($order->points->isEmpty()) {
            throw ValidationException::withMessages([
                'order_id' => 'У заказа нет точек маршрута.',
            ]);
        }

        return DB::transaction(function () use ($executor, $order) {
            Order::query()->whereKey($order->id)->lockForUpdate()->first();

            // Препятствуют только активные выполнения: отменённые и завершённые не мешают новому старту.
            $takenByOther = OrderExecuting::query()
                ->where('order_id', $order->id)
                ->where('executor_id', '!=', $executor->id)
                ->where('status', OrderExecutingStatus::Process)
                ->exists();

            if ($takenByOther) {
                throw ValidationException::withMessages([
                    'order_id' => 'На этот заказ уже назначен исполнитель.',
                ]);
            }

            // Повторный старт возвращает активное выполнение: в работе или на этапе
            // подтверждения кодом — исполнитель не должен терять доступ к экрану.
            $executing = OrderExecuting::query()
                ->where('order_id', $order->id)
                ->where('executor_id', $executor->id)
                ->whereIn('status', [OrderExecutingStatus::Process, OrderExecutingStatus::Confirmation])
                ->lockForUpdate()
                ->first();

            if ($executing) {
                return $this->load($executing);
            }

            if ($order->status !== OrderStatus::Wait) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ недоступен для выполнения.',
                ]);
            }

            $now = now();
            $executing = OrderExecuting::query()->create([
                'order_id' => $order->id,
                'executor_id' => $executor->id,
                'status' => OrderExecutingStatus::Process,
                'process_at' => $now,
            ]);

            foreach ($order->points as $index => $point) {
                $isFirst = $index === 0;
                $executing->points()->create([
                    'order_point_id' => $point->id,
                    'status' => $isFirst ? OrderExecutingStatus::Process : OrderExecutingStatus::Wait,
                    'process_at' => $isFirst ? $now : null,
                ]);
            }

            $order->update(['status' => OrderStatus::Process]);

            return $this->load($executing);
        });
    }

    /**
     * Текущее назначение исполнителя на заказ, включая этап подтверждения кодом.
     *
     * @param  array<string, mixed>  $data
     */
    public function show(User $executor, array $data): OrderExecuting
    {
        $this->assertExecutor($executor);
        $payload = $this->validateIds($data);

        $executing = OrderExecuting::query()
            ->where('order_id', $payload['order_id'])
            ->where('executor_id', $executor->id)
            ->whereIn('status', [
                OrderExecutingStatus::Process,
                OrderExecutingStatus::Confirmation,
            ])
            ->first();

        if (! $executing) {
            throw ValidationException::withMessages([
                'order_id' => 'Выполнение заказа не начато.',
            ]);
        }

        return $this->load($executing);
    }

    /**
     * Завершает текущую точку (complete) и переводит следующую в process.
     * На последней точке завершает всё назначение.
     *
     * @param  array<string, mixed>  $data
     */
    public function completePoint(User $executor, array $data): OrderExecuting
    {
        $this->assertExecutor($executor);

        $payload = $this->validateCompletePoint($data);

        return DB::transaction(function () use ($executor, $payload) {
            // Работаем только с активным выполнением: после отказа остаются отменённые записи.
            $executing = OrderExecuting::query()
                ->where('order_id', $payload['order_id'])
                ->where('executor_id', $executor->id)
                ->where('status', OrderExecutingStatus::Process)
                ->lockForUpdate()
                ->first();

            if (! $executing) {
                throw ValidationException::withMessages([
                    'order_id' => 'Выполнение заказа не начато.',
                ]);
            }

            $executing->load(['points.orderPoint.files']);

            $current = $executing->points
                ->first(fn (OrderExecutingPoint $point) => $point->status === OrderExecutingStatus::Process);

            if (! $current || $current->order_point_id !== $payload['order_point_id']) {
                throw ValidationException::withMessages([
                    'order_point_id' => 'Можно завершить только текущую точку маршрута.',
                ]);
            }

            $now = now();
            $current->update([
                'status' => OrderExecutingStatus::Complete,
                'complete_at' => $now,
            ]);

            $ordered = $executing->points
                ->sortBy(fn (OrderExecutingPoint $point) => $point->orderPoint?->position ?? 0)
                ->values();

            $index = $ordered->search(fn (OrderExecutingPoint $point) => $point->id === $current->id);
            $next = is_int($index) ? $ordered->get($index + 1) : null;

            if ($next instanceof OrderExecutingPoint) {
                $next->update([
                    'status' => OrderExecutingStatus::Process,
                    'process_at' => $now,
                ]);
            } else {
                // Все точки исполнены: ждём подтверждение автора по коду, заказ остаётся в работе.
                $executing->update([
                    'status' => OrderExecutingStatus::Confirmation,
                    'confirmation_at' => $now,
                    'confirmation_number' => random_int(1000, 9999),
                ]);
            }

            return $this->load($executing->refresh());
        });
    }

    /**
     * Снимок выполнения для автора заказа (страница наблюдения).
     * Доступен и на этапе confirmation: все точки пройдены, ждём код от автора.
     *
     * @param  array<string, mixed>  $data
     */
    public function watching(User $author, array $data): OrderExecuting
    {
        $payload = $this->validateIds($data);
        $order = Order::query()->findOrFail($payload['order_id']);

        if ($order->user_id !== $author->id) {
            throw ValidationException::withMessages([
                'order_id' => 'Смотреть выполнение может только автор заказа.',
            ]);
        }

        $executing = OrderExecuting::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if (! $executing) {
            throw ValidationException::withMessages([
                'order_id' => 'Заказ ещё не взят в работу.',
            ]);
        }

        // Наблюдение работает и на этапе подтверждения кодом; закрытые выполнения не показываем.
        if (! in_array($executing->status, [OrderExecutingStatus::Process, OrderExecutingStatus::Confirmation], true)) {
            throw ValidationException::withMessages([
                'order_id' => 'Заказ не находится в процессе.',
            ]);
        }

        return $this->load($executing);
    }

    /**
     * Отменяет заказ по решению автора: снимает исполнителя и закрывает выполнение.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, executing: OrderExecuting|null}
     */
    public function cancel(User $author, array $data): array
    {
        $payload = $this->validateIds($data);
        $order = Order::query()->findOrFail($payload['order_id']);

        if ($order->user_id !== $author->id) {
            throw ValidationException::withMessages([
                'order_id' => 'Отменить можно только собственный заказ.',
            ]);
        }

        return DB::transaction(function () use ($order) {
            $executing = OrderExecuting::query()
                ->where('order_id', $order->id)
                ->where('status', OrderExecutingStatus::Process)
                ->lockForUpdate()
                ->first();

            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ не найден.',
                ]);
            }

            if ($locked->status === OrderStatus::Complete) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ уже исполнен.',
                ]);
            }

            if ($locked->status === OrderStatus::Cancel) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ уже отменён.',
                ]);
            }

            if ($executing) {
                $executing->update([
                    'status' => OrderExecutingStatus::Cancel,
                    'complete_at' => now(),
                ]);
            }

            $locked->update([
                'status' => OrderStatus::Cancel,
                'reason' => 'Заказ отменён заказчиком.',
                'canceled_at' => now(),
            ]);

            return [
                'order' => $locked->load(['points', 'user', 'currentExecuting', 'orderType']),
                'executing' => $executing ? $this->load($executing) : null,
            ];
        });
    }

    /**
     * Отказ исполнителя от выполнения: снимает назначение и возвращает заказ в ожидание.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, executing: OrderExecuting}
     */
    public function decline(User $executor, array $data): array
    {
        $this->assertExecutor($executor);
        $payload = $this->validateIds($data);

        return DB::transaction(function () use ($executor, $payload) {
            $executing = OrderExecuting::query()
                ->where('order_id', $payload['order_id'])
                ->where('executor_id', $executor->id)
                ->where('status', OrderExecutingStatus::Process)
                ->lockForUpdate()
                ->first();

            if (! $executing) {
                throw ValidationException::withMessages([
                    'order_id' => 'Выполнение заказа не начато.',
                ]);
            }

            $order = Order::query()->whereKey($executing->order_id)->lockForUpdate()->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ не найден.',
                ]);
            }

            if ($order->status !== OrderStatus::Process) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ не находится в работе.',
                ]);
            }

            $executing->update([
                'status' => OrderExecutingStatus::Cancel,
                'canceled_at' => now(),
            ]);

            // Заказ снова в ожидании: поиск исполнителей начинается заново.
            $order->update(['status' => OrderStatus::Wait]);

            return [
                'order' => $order->load(['points', 'user', 'currentExecuting', 'orderType']),
                'executing' => $this->load($executing),
            ];
        });
    }

    /**
     * Подтверждает завершение заказа автором: сверяет код и завершает выполнение.
     *
     * @param  array<string, mixed>  $data
     */
    public function confirm(User $executor, array $data): OrderExecuting
    {
        $this->assertExecutor($executor);
        $payload = $this->validateConfirm($data);

        return DB::transaction(function () use ($executor, $payload) {
            $executing = OrderExecuting::query()
                ->where('order_id', $payload['order_id'])
                ->where('executor_id', $executor->id)
                ->where('status', OrderExecutingStatus::Confirmation)
                ->lockForUpdate()
                ->first();

            if (! $executing) {
                throw ValidationException::withMessages([
                    'order_id' => 'Заказ не ожидает подтверждения.',
                ]);
            }

            // Код знает только автор: исполнитель получает его от него.
            if ((string) $executing->confirmation_number !== (string) $payload['code']) {
                throw ValidationException::withMessages([
                    'code' => 'Неверный код подтверждения.',
                ]);
            }

            $executing->update([
                'status' => OrderExecutingStatus::Complete,
                'complete_at' => now(),
            ]);
            $executing->order?->update(['status' => OrderStatus::Complete]);

            return $this->load($executing->refresh());
        });
    }

    /**
     * Активное выполнение: своё как исполнитель либо заказ автора в процессе.
     *
     * @return array{order_id: int, view: 'execute'|'watch'}|null
     */
    public function active(User $user): ?array
    {
        $asExecutor = OrderExecuting::query()
            ->where('executor_id', $user->id)
            ->whereIn('status', [OrderExecutingStatus::Process, OrderExecutingStatus::Confirmation])
            ->latest('process_at')
            ->first();

        if ($asExecutor) {
            return [
                'order_id' => $asExecutor->order_id,
                'view' => 'execute',
            ];
        }

        $asAuthor = OrderExecuting::query()
            ->whereIn('status', [OrderExecutingStatus::Process, OrderExecutingStatus::Confirmation])
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->latest('process_at')
            ->first();

        if ($asAuthor) {
            return [
                'order_id' => $asAuthor->order_id,
                'view' => 'watch',
            ];
        }

        return null;
    }

    /**
     * Сохраняет текущие координаты исполнителя.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLocation(User $executor, array $data): OrderExecuting
    {
        $this->assertExecutor($executor);
        $payload = $this->validateLocation($data);

        $executing = OrderExecuting::query()
            ->where('order_id', $payload['order_id'])
            ->where('executor_id', $executor->id)
            ->where('status', OrderExecutingStatus::Process)
            ->first();

        if (! $executing) {
            throw ValidationException::withMessages([
                'order_id' => 'Выполнение заказа не начато.',
            ]);
        }

        $executing->update([
            'lat' => $payload['lat'],
            'lon' => $payload['lon'],
            'location_at' => now(),
        ]);

        return $this->load($executing->refresh());
    }

    private function assertExecutor(User $user): void
    {
        if ($user->role !== UserRole::Executor) {
            throw ValidationException::withMessages([
                'order_id' => 'Только исполнитель может выполнять заказ.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int}
     */
    private function validateIds(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'order_id.exists' => 'Заказ не найден.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int} $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int, order_point_id: int}
     */
    private function validateCompletePoint(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_point_id' => ['required', 'integer', 'exists:order_points,id'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'order_point_id.required' => 'Укажите точку.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int, order_point_id: int} $validated */
        $validated = $validator->validated();

        $belongs = DB::table('order_points')
            ->where('id', $validated['order_point_id'])
            ->where('order_id', $validated['order_id'])
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'order_point_id' => 'Точка не относится к этому заказу.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int, code: numeric-string}
     */
    private function validateConfirm(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'code' => ['required', 'digits:4'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'order_id.exists' => 'Заказ не найден.',
            'code.required' => 'Введите код подтверждения.',
            'code.digits' => 'Код состоит из 4 цифр.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int, code: numeric-string} $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int, lat: float, lon: float}
     */
    private function validateLocation(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'lat.required' => 'Укажите широту.',
            'lon.required' => 'Укажите долготу.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int, lat: float, lon: float} $validated */
        $validated = $validator->validated();

        return $validated;
    }

    private function load(OrderExecuting $executing): OrderExecuting
    {
        return $executing->load([
            'order.points',
            'order.user',
            'order.currentExecuting.executor.avatar',
            'executor.avatar',
            'points.orderPoint.files',
        ]);
    }
}
