<?php

namespace App\Services;

use App\Http\Resources\OrderMessageResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * RPC-методы чата заказа, вызываемые через Centrifugo.
 */
class OrderMessageRpcService
{
    public function __construct(
        private readonly OrderMessageService $messages,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public function list(User $user, array $data): array
    {
        return OrderMessageResource::collection($this->messages->list($user, $data))
            ->resolve(new Request);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function send(User $user, array $data): array
    {
        return OrderMessageResource::make($this->messages->send($user, $data))
            ->resolve(new Request);
    }
}
