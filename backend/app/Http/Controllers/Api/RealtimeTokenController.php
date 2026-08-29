<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Centrifugo\CentrifugoTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Выдаёт connection JWT для WebSocket-клиента Centrifugo.
 */
class RealtimeTokenController extends Controller
{
    public function __construct(
        private readonly CentrifugoTokenService $tokens,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'token' => $this->tokens->issue($user),
        ]);
    }
}
