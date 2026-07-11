<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/logs')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_logs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, "[2026-07-11 11:00:00] local.WARNING: LINE push failed. {\"to\":\"group-1\"}\n");

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/logs?q=LINE+push+failed')
            ->assertOk()
            ->assertSee('Application Logs')
            ->assertSee('LINE push failed');
    }
}
