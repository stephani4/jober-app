<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверяет, что RPC-запрос пришёл от Centrifugo, а не с клиента напрямую.
 */
class VerifyCentrifugoProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('centrifugo.proxy_secret');
        $actual = (string) $request->header('X-Centrifugo-Proxy-Secret');

        if ($expected === '' || ! hash_equals($expected, $actual)) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Forbidden',
                ],
            ], 403);
        }

        return $next($request);
    }
}
