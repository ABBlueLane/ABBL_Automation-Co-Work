<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenValidationController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        return response()->json([
            'valid' => true,
            'client' => [
                'id' => $client?->id,
                'version' => $client?->version,
                'status' => $client?->status,
            ],
        ]);
    }
}
