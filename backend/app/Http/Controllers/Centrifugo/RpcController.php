<?php

namespace App\Http\Controllers\Centrifugo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationRpcService;
use App\Services\OrderMessageRpcService;
use App\Services\OrderRpcService;
use App\Services\UserProfileRpcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Принимает RPC от Centrifugo и делегирует в сервисы.
 */
class RpcController extends Controller
{
    public function __construct(
        private readonly OrderRpcService $orders,
        private readonly OrderMessageRpcService $messages,
        private readonly NotificationRpcService $notifications,
        private readonly UserProfileRpcService $profile,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->input('user');
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        if (! $user) {
            return $this->rpcError(401, 'Unauthorized');
        }

        $method = (string) $request->input('method');
        
        // Log incoming RPC method for debugging
        \Illuminate\Support\Facades\Log::info('Incoming RPC method: ' . $method, [
            'all_inputs' => $request->all(),
            'user_id' => $userId
        ]);

        /** @var array<string, mixed> $data */
        $data = is_array($request->input('data')) ? $request->input('data') : [];

        try {
            $result = match ($method) {
                'order:create' => $this->orders->create($user, $data),
                'order:mine' => $this->orders->mine($user),
                'order:history' => $this->orders->history($user, $data),
                'order:feed' => $this->orders->feed(),
                'order:start' => $this->orders->start($user, $data),
                'order:executing' => $this->orders->executing($user, $data),
                'order:watching' => $this->orders->watching($user, $data),
                'order:active' => $this->orders->active($user),
                'order:responses_count' => $this->orders->responsesCount($user),
                'order:available_executors_count' => $this->orders->availableExecutorsCount(),
                'order:location' => $this->orders->location($user, $data),
                'order:completePoint' => $this->orders->completePoint($user, $data),
                'order:messages' => $this->messages->list($user, $data),
                'order:message:send' => $this->messages->send($user, $data),
                'notification:list' => $this->notifications->list($user, $data),
                'notification:read' => $this->notifications->read($user, $data),
                'notification:unreadCount' => $this->notifications->unreadCount($user),
                'profile:areas' => $this->profile->listAreas($user),
                'profile:update' => $this->profile->update($user, $data),
                default => null,
            };
        } catch (ValidationException $exception) {
            return $this->rpcError(400, collect($exception->errors())->flatten()->first() ?: 'Validation error');
        } catch (Throwable $exception) {
            report($exception);

            return $this->rpcError(500, 'Internal error');
        }

        if ($result === null) {
            return $this->rpcError(404, 'Unknown method');
        }

        return response()->json([
            'result' => [
                'data' => $result,
            ],
        ]);
    }

    private function rpcError(int $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }
}
