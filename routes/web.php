<?php

use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CriticalIssueController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueCommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueProjectController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('api_clients.index');
});

Route::controller(AuthController::class)->group(function (): void {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.submit');
    Route::post('/logout', 'logout')->name('logout');
});

Route::post('/line/webhook/{secret?}', LineWebhookController::class)
    ->name('line.webhook');

Route::get('/issue/{business}/view/{id}', [IssueController::class, 'view'])
    ->name('issue.view');

Route::middleware('auth')->group(function (): void {
    Route::get('/main', function () {
        return redirect()->route('business.select');
    })->name('main');

    Route::get('/home', function () {
        return redirect()->route('business.select');
    })->name('home');

    Route::get('/select-business', [IssueController::class, 'selectBusiness'])->name('business.select');

    Route::prefix('issue/{business}')->name('issue.')->group(function (): void {
        Route::get('/', [IssueController::class, 'index'])->name('index');
        Route::get('/create', [IssueController::class, 'create'])->name('create');
        Route::post('/draft', [IssueController::class, 'saveDraft'])->name('draft.save');
        Route::post('/store-submit', [IssueController::class, 'storeAndSubmit'])->name('store.submit');
        Route::post('/{issue}/submit', [IssueController::class, 'submitDraft'])->name('submit');
        Route::post('/upload', [IssueController::class, 'upload'])->name('upload');
        Route::get('/table', [IssueController::class, 'table'])->name('table');
        Route::get('/issue/{issue}/comments', [IssueCommentController::class, 'index'])->name('comments.index');
        Route::post('/issue/{issue}/comment', [IssueCommentController::class, 'store'])->name('comment.store');
        Route::post('/issue/{issue}/close', [IssueController::class, 'close'])->name('close');
        Route::get('/{issue}/duplicate', [IssueController::class, 'duplicate'])->name('duplicate');
        Route::post('/preview', [IssueController::class, 'preview'])->name('preview');
    });

    Route::prefix('office/{business}')->name('office.')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/issue', [DashboardController::class, 'issue'])->name('dashboard.issue');
        Route::get('/dashboard/issue/stats', [DashboardController::class, 'issueStats'])->name('dashboard.issue.stats');
        Route::get('/dashboard/issue/modal/{id}', [DashboardController::class, 'issueModal'])->name('dashboard.issue.modal');

        Route::prefix('issue')->name('issue.')->group(function (): void {
            Route::get('/', [IssueController::class, 'staffIndex'])->name('index');
            Route::get('/table', [IssueController::class, 'staffTable'])->name('table');
            Route::post('/upload', [IssueController::class, 'staffUpload'])->name('upload');
            Route::get('/{id}/assign-modal', [IssueController::class, 'staffAssignModal'])->name('assign.modal');
            Route::post('/{id}/assign', [IssueController::class, 'staffAssign'])->name('assign');
            Route::post('/{id}/close', [IssueController::class, 'staffClose'])->name('close');
            Route::post('/{id}/review', [IssueController::class, 'staffReview'])->name('review');
            Route::post('/{id}/comment', [IssueCommentController::class, 'staffStore'])->name('comment.store');
            Route::post('/{id}/priority', [IssueController::class, 'staffPriority'])->name('priority');
            Route::get('/{id}', [IssueController::class, 'staffView'])->name('view');
        });

        Route::prefix('issue/project')->name('issue.project.')->group(function (): void {
            Route::get('/', [IssueProjectController::class, 'index'])->name('index');
            Route::get('/table', [IssueProjectController::class, 'table'])->name('table');
            Route::get('/modal/add', [IssueProjectController::class, 'modalAdd'])->name('modal.add');
            Route::get('/{id}/modal/edit', [IssueProjectController::class, 'modalEdit'])->name('modal.edit');
            Route::post('/', [IssueProjectController::class, 'store'])->name('store');
            Route::post('/{id}', [IssueProjectController::class, 'update'])->name('update');
            Route::delete('/{id}', [IssueProjectController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('issue/critical')->name('issue.critical.')->group(function (): void {
            Route::get('/', [CriticalIssueController::class, 'index'])->name('index');
            Route::get('/table', [CriticalIssueController::class, 'table'])->name('table');
            Route::get('/modal/add', [CriticalIssueController::class, 'modalAdd'])->name('modal.add');
            Route::get('/{id}/modal/edit', [CriticalIssueController::class, 'modalEdit'])->name('modal.edit');
            Route::post('/', [CriticalIssueController::class, 'store'])->name('store');
            Route::post('/{id}', [CriticalIssueController::class, 'update'])->name('update');
            Route::delete('/{id}', [CriticalIssueController::class, 'destroy'])->name('destroy');
        });
    });

    Route::controller(ApiClientController::class)->prefix('api-clients')->name('api_clients.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{apiClient}/edit', 'edit')->name('edit');
        Route::put('/{apiClient}', 'update')->name('update');
    });

    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
        Route::post('/{user}/change-status', 'changeStatus')->name('changeStatus');
    });

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});
