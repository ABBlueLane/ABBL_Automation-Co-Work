<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (! is_string($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Missing or invalid Authorization header'], 401);
        }

        $plainToken = trim(substr($authHeader, 7));

        if ($plainToken === '') {
            return response()->json(['message' => 'Missing or invalid Authorization header'], 401);
        }

        $client = ApiClient::query()
            ->where('token_hash', ApiClient::hashToken($plainToken))
            ->where('status', 'active')
            ->first();

        if (! $client) {
            return response()->json(['message' => 'Unauthorized token'], 401);
        }

        $client->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
