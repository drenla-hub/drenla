<?php

namespace App\Http\Controllers\Api;

use App\AiAssistant\Services\PublicChatAssistant;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public, unauthenticated API for the (not-yet-built) site chat widget.
 * Anonymous-visitor only for now — a session is identified purely by its
 * `session_token`, the same trust model as the existing Client magic-link
 * portal token (whoever holds the token can read/post to that conversation).
 *
 * Persists to the chat_sessions/chat_messages tables that already existed as
 * an "audit trail ready to receive rows" — this is what starts writing to them.
 */
class PublicChatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $session = ChatSession::create([
            'session_token' => (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'ai_enabled' => true,
        ]);

        return response()->json($this->sessionPayload($session), 201);
    }

    public function show(Request $request, string $sessionToken): JsonResponse
    {
        $session = ChatSession::where('session_token', $sessionToken)->firstOrFail();

        return response()->json($this->sessionPayload($session));
    }

    public function messages(Request $request, string $sessionToken): JsonResponse
    {
        $session = ChatSession::where('session_token', $sessionToken)->firstOrFail();
        $afterId = (int) $request->query('after_id', 0);

        $messages = $session->messages()
            ->where('id', '>', $afterId)
            ->get()
            ->map(fn (ChatMessage $message) => $this->publicMessage($message));

        return response()->json([
            'messages' => $messages,
            'ai_enabled' => $session->ai_enabled,
        ]);
    }

    public function storeMessage(Request $request, string $sessionToken, PublicChatAssistant $assistant): JsonResponse
    {
        $session = ChatSession::where('session_token', $sessionToken)->firstOrFail();

        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'visitor' => ['sometimes', 'array'],
            'visitor.name' => ['nullable', 'string', 'max:255'],
            'visitor.email' => ['nullable', 'email', 'max:255'],
            'visitor.phone' => ['nullable', 'string', 'max:255'],
        ]);

        if ($visitor = $data['visitor'] ?? null) {
            $session->fill([
                'visitor_name' => $visitor['name'] ?? $session->visitor_name,
                'visitor_email' => $visitor['email'] ?? $session->visitor_email,
                'visitor_phone' => $visitor['phone'] ?? $session->visitor_phone,
            ]);
        }

        $visitorMessage = $session->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $session->last_message_at = now();
        $session->save();

        $reply = null;

        if ($session->ai_enabled) {
            $history = $session->messages()
                ->where('id', '!=', $visitorMessage->id)
                ->get()
                ->map(fn (ChatMessage $message) => [
                    // Both AI and human-admin replies read as "the assistant side" of
                    // the conversation to the model — the visitor doesn't distinguish,
                    // and there's no reason to confuse the LLM with a third role.
                    'role' => $message->role === 'user' ? 'user' : 'assistant',
                    'content' => $message->content,
                ])
                ->all();

            $result = $assistant->respond(
                prompt: $data['message'],
                context: ['visitor_name' => $session->visitor_name],
                history: $history,
            );

            $replyMessage = $session->messages()->create([
                'role' => 'assistant',
                'content' => $result->message,
                'tool_call' => $result->toolResults ?: null,
            ]);

            $session->last_message_at = now();
            $session->save();

            $reply = $this->publicMessage($replyMessage);
        }

        return response()->json([
            'message' => $this->publicMessage($visitorMessage),
            'reply' => $reply,
        ], 201);
    }

    /** @return array<string, mixed> */
    private function sessionPayload(ChatSession $session): array
    {
        return [
            'session_token' => $session->session_token,
            'ai_enabled' => $session->ai_enabled,
            'visitor_name' => $session->visitor_name,
            'visitor_email' => $session->visitor_email,
            'visitor_phone' => $session->visitor_phone,
            'messages' => $session->messages->map(fn (ChatMessage $message) => $this->publicMessage($message)),
        ];
    }

    /**
     * The visitor-facing message shape — deliberately narrower than the full
     * model: never exposes `tool_call` (internal reasoning detail) or which
     * admin user replied (an anonymous visitor doesn't need or get that).
     *
     * @return array<string, mixed>
     */
    private function publicMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role === 'admin' ? 'assistant' : $message->role,
            'content' => $message->content,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
