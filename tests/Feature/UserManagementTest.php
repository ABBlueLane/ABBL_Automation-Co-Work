<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('users.store'), [
                'first_name' => 'Office',
                'last_name' => 'Admin',
                'nick_name' => 'OA',
                'email' => 'office@example.com',
                'phone_no' => '0899999999',
                'status' => 'active',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'office@example.com',
            'first_name' => 'Office',
            'status' => 'active',
        ]);
    }
}
