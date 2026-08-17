<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\FocusArea;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'clients' => Client::count(),
            'inquiries' => Inquiry::count(),
            'active_projects' => Project::whereIn('status', ['planned', 'active', 'blocked', 'on_hold'])->count(),
            'open_proposals' => Proposal::whereIn('status', ['draft', 'sent', 'accepted'])->count(),
            'published_case_studies' => CaseStudy::published()->count(),
            'published_articles' => Article::published()->count(),
            'published_focus_areas' => FocusArea::published()->count(),
            'outstanding_invoice_total' => FinanceDocument::query()
                ->where('type', 'invoice')
                ->sum(DB::raw('total_amount - amount_paid')),
        ];

        $recentInquiries = Inquiry::with('assignee')->latest()->take(5)->get();
        $recentProjects = Project::with('client')->latest()->take(5)->get();
        $recentDocuments = FinanceDocument::with('client')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentInquiries', 'recentProjects', 'recentDocuments'));
    }
}
