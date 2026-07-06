<?php

namespace App\Services\Line;

class LineSignatureVerifier
{
    public function verify(string $rawBody, ?string $receivedSignature, ?string $channelSecret): bool
    {
        if ($receivedSignature === null || $receivedSignature === '' || $channelSecret === null || $channelSecret === '') {
            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $rawBody, $channelSecret, true));

        return hash_equals($expectedSignature, $receivedSignature);
    }
}
