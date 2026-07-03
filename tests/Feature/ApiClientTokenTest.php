<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiClientTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_client_token_can_be_created_and_used(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('api_clients.store'), [
            'version' => 'Automation Client',
            'description' => 'Used by external automation jobs.',
            'status' => 'active',
        ]);

        $response
            ->assertRedirect(route('api_clients.index'))
            ->assertSessionHas('generated_token');

        $plainToken = session('generated_token');
        $this->assertIsString($plainToken);
        $this->assertSame(60, strlen($plainToken));

        $client = ApiClient::query()->firstOrFail();

        $this->assertNotSame($plainToken, $client->token_hash);
        $this->assertSame(ApiClient::hashToken($plainToken), $client->token_hash);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/check-token')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'client' => [
                    'id' => $client->id,
                    'version' => 'Automation Client',
                    'status' => 'active',
                ],
            ]);

        $this->assertNotNull($client->fresh()->last_used_at);
    }

    public function test_api_request_without_valid_token_is_rejected(): void
    {
        $this->getJson('/api/check-token')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Missing or invalid Authorization header']);

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/check-token')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized token']);
    }
}
