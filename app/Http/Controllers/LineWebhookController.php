<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLineWebhookEvent;
use App\Services\Line\LineSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request, LineSignatureVerifier $verifier, ?string $secret = null): JsonResponse
    {
        $configuredSecret = config('services.line.webhook_route_secret');

        if ($configuredSecret !== null && $configuredSecret !== '' && ! hash_equals($configuredSecret, (string) $secret)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $rawBody = $request->getContent();
        $signature = $request->header('x-line-signature');
        $signatureValid = $verifier->verify($rawBody, $signature, config('services.line.channel_secret'));

        if (! $signatureValid) {
            $this->logWebhook(false, $rawBody, null, 0, 'Invalid LINE signature.');

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            $this->logWebhook(true, $rawBody, null, 0, 'Invalid JSON payload.');

            return response()->json(['message' => 'Invalid JSON'], 400);
        }

        $events = $payload['events'] ?? [];

        if (! is_array($events)) {
            $events = [];
        }

        $this->logWebhook(true, $rawBody, $payload['destination'] ?? null, count($events));

        foreach ($events as $event) {
            if (is_array($event)) {
                ProcessLineWebhookEvent::dispatchSync($event);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function logWebhook(bool $signatureValid, string $rawBody, ?string $destination, int $eventCount, ?string $errorMessage = null): void
    {
        DB::table('line_webhook_logs')->insert([
            'signature_valid' => $signatureValid,
            'destination' => $destination,
            'event_count' => $eventCount,
            'raw_body_hash' => hash('sha256', $rawBody),
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
