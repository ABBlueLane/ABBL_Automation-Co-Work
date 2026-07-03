<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiClientController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');

        if (! in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $clients = ApiClient::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('api_clients.index', [
            'clients' => $clients,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('api_clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $plainToken = Str::random(60);

        ApiClient::query()->create([
            ...$validated,
            'token_hash' => ApiClient::hashToken($plainToken),
        ]);

        return redirect()
            ->route('api_clients.index')
            ->with('generated_token', $plainToken)
            ->with('success', 'API token created. Copy it now; it will not be shown again.');
    }

    public function edit(ApiClient $apiClient): View
    {
        return view('api_clients.edit', ['apiClient' => $apiClient]);
    }

    public function update(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $apiClient->update($validated);

        return redirect()
            ->route('api_clients.index')
            ->with('success', 'API client updated.');
    }
}
