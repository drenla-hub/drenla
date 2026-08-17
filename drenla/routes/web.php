<?php

use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CaseStudyController;
use App\Http\Controllers\Admin\ChatSessionController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceDocumentController;
use App\Http\Controllers\Admin\FocusAreaController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProposalController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

// Signed PDF-print routes — accessed by Chrome headless during export (no session required)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/proposals/{proposal}/print', [ProposalController::class, 'printPdf'])
        ->middleware('signed')
        ->name('proposals.print');

    Route::get('/finance/{finance}/print', [FinanceDocumentController::class, 'printPdf'])
        ->middleware('signed')
        ->name('finance.print');
});

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::post('/ai-assistant/respond', AiAssistantController::class)->name('ai-assistant.respond');

    // ── Journal (private per-user AI thinking space — no extra permission,
    // ── every user only ever sees their own conversations; see JournalController) ─
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::post('/journal/start', [JournalController::class, 'quickStart'])->name('journal.quick-start');
    Route::get('/journal/attachments/{attachment}', [JournalController::class, 'showAttachment'])->name('journal.attachments.show');
    Route::get('/journal/{journalConversation}', [JournalController::class, 'show'])->name('journal.show');
    Route::patch('/journal/{journalConversation}', [JournalController::class, 'update'])->name('journal.update');
    Route::delete('/journal/{journalConversation}', [JournalController::class, 'destroy'])->name('journal.destroy');
    Route::post('/journal/{journalConversation}/messages', [JournalController::class, 'sendMessage'])->name('journal.messages.store');

    Route::get('/', DashboardController::class)->name('dashboard');

    // ── Users & Roles ────────────────────────────────────────────────────
    Route::middleware('permission:view_users,manage_users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'edit']);
        Route::resource('roles', RoleController::class)->only(['index', 'create', 'edit']);
    });
    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class)->only(['store', 'update', 'destroy']);
        Route::resource('roles', RoleController::class)->only(['store', 'update', 'destroy']);
    });

    // ── Settings ─────────────────────────────────────────────────────────
    Route::middleware('permission:view_settings,manage_settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    });
    Route::middleware('permission:manage_settings')->group(function () {
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // ── Content ──────────────────────────────────────────────────────────
    Route::middleware('permission:view_content,manage_content')->group(function () {
        Route::resource('focus-areas', FocusAreaController::class)->only(['index', 'create', 'edit']);
        Route::resource('case-studies', CaseStudyController::class)->only(['index', 'create', 'edit']);
        Route::resource('articles', ArticleController::class)->only(['index', 'create', 'edit']);
        Route::resource('homepage', HomepageSectionController::class)->only(['index', 'create', 'edit'])->parameter('homepage', 'section');
    });
    Route::middleware('permission:manage_content')->group(function () {
        Route::resource('focus-areas', FocusAreaController::class)->only(['store', 'update', 'destroy']);
        Route::resource('case-studies', CaseStudyController::class)->only(['store', 'update', 'destroy']);
        Route::resource('articles', ArticleController::class)->only(['store', 'update', 'destroy']);
        Route::resource('homepage', HomepageSectionController::class)->only(['store', 'update', 'destroy'])->parameter('homepage', 'section');
    });

    // ── Media ────────────────────────────────────────────────────────────
    Route::middleware('permission:view_media,manage_media')->group(function () {
        Route::resource('media', MediaAssetController::class)->only(['index'])->parameter('media', 'media');
    });
    Route::middleware('permission:manage_media')->group(function () {
        Route::resource('media', MediaAssetController::class)->only(['store', 'destroy'])->parameter('media', 'media');
    });

    // ── Clients & Inquiries ──────────────────────────────────────────────
    Route::middleware('permission:view_clients,manage_clients')->group(function () {
        Route::resource('clients', ClientController::class)->only(['index', 'create', 'edit']);
        Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    });
    Route::middleware('permission:manage_clients')->group(function () {
        Route::resource('clients', ClientController::class)->only(['store', 'update', 'destroy']);
        Route::post('/clients/{client}/regenerate-token', [ClientController::class, 'regeneratePortalToken'])->name('clients.regenerate-token');
        Route::post('/clients/{client}/contacts', [ClientController::class, 'storeContact'])->name('clients.contacts.store');
        Route::delete('/clients/{client}/contacts/{contact}', [ClientController::class, 'destroyContact'])->name('clients.contacts.destroy');
        Route::post('/clients/{client}/lead-notes', [ClientController::class, 'storeLeadNote'])->name('clients.lead-notes.store');
        Route::patch('/inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('/inquiries/{inquiry}/lead-notes', [InquiryController::class, 'storeLeadNote'])->name('inquiries.lead-notes.store');
    });

    // ── Chat conversations (AI widget audit trail + admin takeover) ─────
    Route::middleware('permission:view_chat,manage_chat')->group(function () {
        Route::get('/chat', [ChatSessionController::class, 'index'])->name('chat.index');
        Route::get('/chat/{chatSession}', [ChatSessionController::class, 'show'])->name('chat.show');
        Route::get('/chat/{chatSession}/messages', [ChatSessionController::class, 'messages'])->name('chat.messages');
    });
    Route::middleware('permission:manage_chat')->group(function () {
        Route::post('/chat/{chatSession}/reply', [ChatSessionController::class, 'reply'])->name('chat.reply');
        Route::post('/chat/{chatSession}/resume-ai', [ChatSessionController::class, 'resumeAi'])->name('chat.resume-ai');
        Route::delete('/chat/{chatSession}', [ChatSessionController::class, 'destroy'])->name('chat.destroy');
    });

    // ── Proposals ────────────────────────────────────────────────────────
    Route::middleware('permission:view_proposals,manage_proposals')->group(function () {
        Route::get('/proposals/template-defaults', [ProposalController::class, 'templateDefaults'])->name('proposals.template-defaults');
        Route::get('/proposals/{proposal}/preview', [ProposalController::class, 'preview'])->name('proposals.preview');
        Route::get('/proposals/{proposal}/export', [ProposalController::class, 'export'])->name('proposals.export');
        Route::resource('proposals', ProposalController::class)->only(['index', 'create', 'edit']);
    });
    Route::middleware('permission:manage_proposals')->group(function () {
        Route::post('/proposals/{proposal}/send', [ProposalController::class, 'send'])->name('proposals.send');
        Route::resource('proposals', ProposalController::class)->only(['store', 'update', 'destroy']);
    });

    // ── Projects ─────────────────────────────────────────────────────────
    Route::middleware('permission:view_projects,manage_projects')->group(function () {
        Route::resource('projects', ProjectController::class)->only(['index', 'create', 'show', 'edit']);
        Route::get('/projects/{project}/tasks/{task}/comments', [ProjectController::class, 'taskComments'])->name('projects.tasks.comments.index');
    });
    Route::middleware('permission:manage_projects')->group(function () {
        Route::resource('projects', ProjectController::class)->only(['store', 'update', 'destroy']);

        Route::post('/projects/{project}/milestones', [ProjectController::class, 'storeMilestone'])->name('projects.milestones.store');
        Route::patch('/projects/{project}/milestones/{milestone}', [ProjectController::class, 'updateMilestone'])->name('projects.milestones.update');
        Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
        Route::patch('/projects/{project}/tasks/{task}', [ProjectController::class, 'updateTask'])->name('projects.tasks.update');
        Route::patch('/projects/{project}/tasks/{task}/timeline', [ProjectController::class, 'updateTaskTimeline'])->name('projects.tasks.timeline');
        Route::delete('/projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');
        Route::post('/projects/{project}/tasks/{task}/comments', [ProjectController::class, 'storeTaskComment'])->name('projects.tasks.comments.store');
    });

    // ── Finance ──────────────────────────────────────────────────────────
    Route::middleware('permission:view_finance,manage_finance')->group(function () {
        Route::get('/finance/{finance}/preview', [FinanceDocumentController::class, 'preview'])->name('finance.preview');
        Route::get('/finance/{finance}/export', [FinanceDocumentController::class, 'export'])->name('finance.export');
        Route::resource('finance', FinanceDocumentController::class)->only(['index', 'create', 'edit'])->parameter('finance', 'finance');
    });
    Route::middleware('permission:manage_finance')->group(function () {
        Route::post('/finance/{finance}/send', [FinanceDocumentController::class, 'send'])->name('finance.send');
        Route::resource('finance', FinanceDocumentController::class)->only(['store', 'update', 'destroy'])->parameter('finance', 'finance');
    });
});
