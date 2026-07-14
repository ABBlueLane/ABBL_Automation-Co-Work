<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'phone_no' => '0812345678',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $this->post(route('login.submit'), [
            'login' => 'admin@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => 'phone@example.com',
            'phone_no' => '081-234-5678',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $this->post(route('login.submit'), [
            'login' => '0812345678',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'inactive',
        ]);

        $this->post(route('login.submit'), [
            'login' => 'inactive@example.com',
            'password' => 'secret123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
