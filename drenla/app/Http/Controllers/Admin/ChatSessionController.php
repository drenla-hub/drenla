<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatSessionController extends Controller
{
    public function index(): View
    {
        $sessions = ChatSession::withCount('messages')
            ->latest('last_message_at')
            ->paginate(20);

        return view('admin.chat.index', compact('sessions'));
    }

    public function show(ChatSession $chatSession): View
    {
        $chatSession->load('messages.sender');

        return view('admin.chat.show', compact('chatSession'));
    }

    /** JSON polling endpoint for the live transcript view. */
    public function messages(Request $request, ChatSession $chatSession): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);

        $messages = $chatSession->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'sender_name' => $message->sender?->name,
                'created_at' => $message->created_at->format('H:i'),
            ]);

        return response()->json([
            'messages' => $messages,
            'ai_enabled' => $chatSession->ai_enabled,
        ]);
    }

    /** An admin taking over the conversation — pauses the AI until explicitly resumed. */
    public function reply(Request $request, ChatSession $chatSession): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $chatSession->messages()->create([
            'role' => 'admin',
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $chatSession->update([
            'ai_enabled' => false,
            'last_message_at' => now(),
        ]);

        return redirect()->route('admin.chat.show', $chatSession)->with('status', 'Reply sent.');
    }

    public function resumeAi(ChatSession $chatSession): RedirectResponse
    {
        $chatSession->update(['ai_enabled' => true]);

        return redirect()->route('admin.chat.show', $chatSession)->with('status', 'AI resumed for this conversation.');
    }

    public function destroy(ChatSession $chatSession): RedirectResponse
    {
        $chatSession->delete();

        return redirect()->route('admin.chat.index')->with('status', 'Session deleted.');
    }
}
