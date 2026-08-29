<?php

namespace App\Services;

use App\Enums\OrderExecutingStatus;
use App\Http\Resources\OrderMessageResource;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Centrifugo\CentrifugoClient;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Чат заказа: список, отправка и realtime-публикация участникам.
 */
class OrderMessageService
{
    public const PAGE_SIZE = 100;

    public function __construct(
        private readonly CentrifugoClient $centrifugo,
        private readonly CentrifugoTokenService $tokens,
    ) {}

    /**
     * Сообщения заказа от старых к новым.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, OrderMessage>
     */
    public function list(User $user, array $data): Collection
    {
        $payload = $this->validateOrderId($data);
        $this->assertParticipant($user, $this->orderForChat($payload['order_id']));

        return OrderMessage::query()
            ->where('order_id', $payload['order_id'])
            ->with('user')
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Сохраняет сообщение и пушит его автору заказа и исполнителю.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, array $data): OrderMessage
    {
        $payload = $this->validateSend($data);
        $order = $this->orderForChat($payload['order_id']);
        $this->assertParticipant($user, $order);
        $this->assertInProcess($order);

        $message = OrderMessage::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'body' => $payload['body'],
        ])->load('user');

        $this->publishCreated($order, $message);

        return $message;
    }

    /**
     * Заказ с текущим назначением исполнителя.
     */
    private function orderForChat(int $orderId): Order
    {
        $order = Order::query()->with(['user', 'currentExecuting.executor'])->find($orderId);
        if (! $order) {
            throw ValidationException::withMessages([
                'order_id' => 'Заказ не найден.',
            ]);
        }

        return $order;
    }

    /**
     * Писать и читать чат могут только автор заказа и назначенный исполнитель.
     */
    private function assertParticipant(User $user, Order $order): void
    {
        $executorId = $order->currentExecuting?->executor_id;
        if ($order->user_id === $user->id || $executorId === $user->id) {
            return;
        }

        throw ValidationException::withMessages([
            'order_id' => 'Чат доступен только участникам заказа.',
        ]);
    }

    /**
     * Отправка разрешена, пока заказ выполняется.
     */
    private function assertInProcess(Order $order): void
    {
        if ($order->currentExecuting?->status === OrderExecutingStatus::Process) {
            return;
        }

        throw ValidationException::withMessages([
            'order_id' => 'Чат доступен только во время выполнения заказа.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int}
     */
    private function validateOrderId(array $data): array
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
     * @return array{order_id: int, body: string}
     */
    private function validateSend(array $data): array
    {
        $validator = Validator::make($data, [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'order_id.required' => 'Укажите заказ.',
            'order_id.exists' => 'Заказ не найден.',
            'body.required' => 'Введите сообщение.',
            'body.max' => 'Сообщение слишком длинное.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{order_id: int, body: string} $validated */
        $validated = $validator->validated();
        $body = trim($validated['body']);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Введите сообщение.',
            ]);
        }

        return [
            'order_id' => $validated['order_id'],
            'body' => $body,
        ];
    }

    /**
     * Публикует новое сообщение в личные каналы участников.
     */
    private function publishCreated(Order $order, OrderMessage $message): void
    {
        $payload = [
            'type' => 'order.message',
            'message' => OrderMessageResource::make($message)->resolve(new Request),
        ];

        $channels = [];
        $author = $order->user;
        if ($author) {
            $channels[] = $this->tokens->personalChannel($author);
        }

        $executor = $order->currentExecuting?->executor;
        if ($executor && $executor->id !== $author?->id) {
            $channels[] = $this->tokens->personalChannel($executor);
        }

        if ($channels === []) {
            return;
        }

        try {
            $this->centrifugo->broadcast($channels, $payload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
