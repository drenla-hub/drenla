<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(): View
    {
        $inquiries = Inquiry::with(['assignee', 'client'])->latest()->get();

        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function show(Inquiry $inquiry): View
    {
        $users = User::orderBy('name')->get();
        $inquiry->load('leadNotes.author');

        return view('admin.inquiries.show', compact('inquiry', 'users'));
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update($request->validate([
            'status' => ['required', 'in:new,qualified,proposal_sent,won,lost'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Inquiry updated.');
    }

    public function storeLeadNote(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $inquiry->leadNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('admin.inquiries.show', $inquiry)->with('status', 'Note added.');
    }
}
