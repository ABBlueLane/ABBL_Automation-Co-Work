<?php

namespace Tests\Unit;

use App\Services\Line\LineSignatureVerifier;
use PHPUnit\Framework\TestCase;

class LineSignatureVerifierTest extends TestCase
{
    public function test_valid_signature_is_accepted(): void
    {
        $rawBody = '{"events":[]}';
        $secret = 'line-secret';
        $signature = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        $this->assertTrue((new LineSignatureVerifier)->verify($rawBody, $signature, $secret));
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->assertFalse((new LineSignatureVerifier)->verify('{"events":[]}', 'invalid', 'line-secret'));
    }

    public function test_missing_signature_or_secret_is_rejected(): void
    {
        $verifier = new LineSignatureVerifier;

        $this->assertFalse($verifier->verify('{"events":[]}', null, 'line-secret'));
        $this->assertFalse($verifier->verify('{"events":[]}', 'signature', null));
    }
}
