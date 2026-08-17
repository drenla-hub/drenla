<?php

use App\Http\Controllers\Auth\ClientPortalAuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\FinanceDocumentController;
use App\Http\Controllers\Portal\ProjectController;
use App\Http\Controllers\Portal\ProposalController;
use Illuminate\Support\Facades\Route;

// Magic-link entry — the URL CL's admin "copy portal link" button generates.
Route::get('/portal/access/{token}', [ClientPortalAuthController::class, 'access'])
    ->name('portal.access');

Route::middleware('guest:client')->group(function () {
    Route::get('/portal/login', [ClientPortalAuthController::class, 'create'])->name('portal.login');
    Route::post('/portal/login', [ClientPortalAuthController::class, 'store'])->name('portal.login.store');
});

Route::middleware(['auth:client', 'client.portal'])->prefix('portal')->name('portal.')->group(function () {
    Route::post('/logout', [ClientPortalAuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{proposal}/download', [ProposalController::class, 'download'])->name('proposals.download');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('/finance', [FinanceDocumentController::class, 'index'])->name('finance.index');
    Route::get('/finance/{financeDocument}', [FinanceDocumentController::class, 'show'])->name('finance.show');
});
