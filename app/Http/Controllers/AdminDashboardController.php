<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\Business;
use App\Models\Issue;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $openIssueStatuses = [
            Issue::STATUS_PENDING,
            Issue::STATUS_IN_PROGRESS,
            Issue::STATUS_WAITING_REVIEW,
            Issue::STATUS_CUSTOMER_REPLIED,
        ];

        return view('dashboard.index', [
            'stats' => [
                'users_total' => User::query()->count(),
                'users_active' => User::query()->where('status', 'active')->count(),
                'api_clients_active' => ApiClient::query()->where('status', 'active')->count(),
                'businesses_total' => Business::query()->count(),
                'issues_open' => Issue::query()
                    ->where('status', '!=', Issue::STATUS_DRAFT)
                    ->whereIn('status', $openIssueStatuses)
                    ->count(),
            ],
        ]);
    }
}
