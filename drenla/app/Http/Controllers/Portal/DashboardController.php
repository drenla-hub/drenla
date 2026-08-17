<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $client = $request->user('client');

        $proposals = $client->proposals()
            ->where('is_client_visible', true)
            ->latest()
            ->get();

        $projects = $client->projects()
            ->with('milestones')
            ->latest()
            ->get();

        $financeDocuments = $client->financeDocuments()
            ->latest('issue_date')
            ->take(5)
            ->get();

        return view('portal.dashboard', compact('client', 'proposals', 'projects', 'financeDocuments'));
    }
}
