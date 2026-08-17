<?php

namespace App\Http\Controllers\Admin;

use App\AiAssistant\Services\JournalAssistant;
use App\Http\Controllers\Controller;
use App\Models\JournalConversation;
use App\Models\JournalMessage;
use App\Models\JournalMessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * A private, per-admin-user AI thinking space — "own conversations only" is
 * enforced explicitly in every action below (route-model binding resolves
 * *a* JournalConversation by ID, it does not check who owns it), gated by
 * simply being a logged-in admin (no separate permission constant — every
 * user only ever sees their own rows here, there's nothing to gate further).
 */
class JournalController extends Controller
{
    private const ALLOWED_ATTACHMENT_MIMES = 'jpg,jpeg,png,gif,webp,pdf,txt,md,csv,json';

    private const MAX_ATTACHMENTS = 4;

    private const MAX_ATTACHMENT_KB = 8192;

    public function index(Request $request): View
    {
        $conversations = $request->user()->journalConversations()->latest('last_message_at')->get();

        return view('admin.journal.index', [
            'conversations' => $conversations,
            'conversation' => null,
        ]);
    }

    public function show(Request $request, JournalConversation $journalConversation): View
    {
        $this->authorizeOwner($request, $journalConversation);

        $conversations = $request->user()->journalConversations()->latest('last_message_at')->get();
        $journalConversation->load('messages.attachments');

        return view('admin.journal.index', [
            'conversations' => $conversations,
            'conversation' => $journalConversation,
        ]);
    }

    public function update(Request $request, JournalConversation $journalConversation): RedirectResponse
    {
        $this->authorizeOwner($request, $journalConversation);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $journalConversation->update(['title' => $data['title']]);

        return redirect()->route('admin.journal.show', $journalConversation)->with('status', 'Renamed.');
    }

    public function destroy(Request $request, JournalConversation $journalConversation): RedirectResponse
    {
        $this->authorizeOwner($request, $journalConversation);

        $journalConversation->delete();

        return redirect()->route('admin.journal.index')->with('status', 'Conversation deleted.');
    }

    public function store(Request $request): RedirectResponse
    {
        $conversation = $request->user()->journalConversations()->create([
            'title' => 'New conversation',
        ]);

        return redirect()->route('admin.journal.show', $conversation);
    }

    /**
     * A "+ New" click never touches the database — the conversation only
     * gets created here, once the user actually sends a first message, so
     * navigating away from a blank composer leaves nothing behind.
     */
    public function quickStart(Request $request, JournalAssistant $assistant): JsonResponse
    {
        [$prompt, $files] = $this->validatePromptAndFiles($request);

        $title = $prompt !== '' ? $prompt : ($files[0]->getClientOriginalName() ?? 'New conversation');

        $conversation = $request->user()->journalConversations()->create([
            'title' => Str::limit($title, 60),
        ]);

        $this->respondTo($conversation, $prompt, $assistant, $files);

        return response()->json([
            'redirect' => route('admin.journal.show', $conversation),
        ]);
    }

    public function sendMessage(Request $request, JournalConversation $journalConversation, JournalAssistant $assistant): JsonResponse
    {
        $this->authorizeOwner($request, $journalConversation);

        [$prompt, $files] = $this->validatePromptAndFiles($request);

        if (blank($journalConversation->title) || $journalConversation->title === 'New conversation') {
            $title = $prompt !== '' ? $prompt : ($files[0]->getClientOriginalName() ?? 'New conversation');
            $journalConversation->update(['title' => Str::limit($title, 60)]);
        }

        ['user' => $userMessage, 'assistant' => $replyMessage] = $this->respondTo($journalConversation, $prompt, $assistant, $files);

        return response()->json([
            'message' => [
                'id' => $replyMessage->id,
                'role' => 'assistant',
                'content' => $replyMessage->content,
                'content_html' => $replyMessage->renderedContent(),
            ],
            'user_message' => [
                'id' => $userMessage->id,
                'attachments' => $userMessage->attachments->map(fn (JournalMessageAttachment $attachment) => $this->attachmentPayload($attachment))->all(),
            ],
            'title' => $journalConversation->fresh()->title,
        ]);
    }

    public function showAttachment(Request $request, JournalMessageAttachment $attachment): Response
    {
        $attachment->loadMissing('message.conversation');

        abort_unless($attachment->message->conversation->user_id === $request->user()->id, 403);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name);
    }

    /**
     * @return array{0: string, 1: array<int, UploadedFile>}
     */
    private function validatePromptAndFiles(Request $request): array
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:8000'],
            'files' => ['sometimes', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'files.*' => ['file', 'max:'.self::MAX_ATTACHMENT_KB, 'mimes:'.self::ALLOWED_ATTACHMENT_MIMES],
        ]);

        $prompt = trim((string) ($data['prompt'] ?? ''));
        $files = $request->file('files', []);

        if ($prompt === '' && $files === []) {
            throw ValidationException::withMessages(['prompt' => 'Say something or attach a file.']);
        }

        return [$prompt, $files];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array{user: JournalMessage, assistant: JournalMessage}
     */
    private function respondTo(JournalConversation $journalConversation, string $prompt, JournalAssistant $assistant, array $files = []): array
    {
        $userMessage = $journalConversation->messages()->create([
            'role' => 'user',
            'content' => $prompt,
        ]);

        foreach ($files as $file) {
            $path = $file->store('journal-attachments/'.$journalConversation->id, 'local');

            $userMessage->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }

        $userMessage->load('attachments');

        $history = $journalConversation->messages()
            ->where('id', '!=', $userMessage->id)
            ->with('attachments')
            ->get()
            ->map(fn (JournalMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
                'attachments' => $message->attachments->map(fn (JournalMessageAttachment $attachment) => $attachment->toAiPayload())->all(),
            ])
            ->all();

        $result = $assistant->respond(
            prompt: $prompt,
            history: $history,
            attachments: $userMessage->attachments->map(fn (JournalMessageAttachment $attachment) => $attachment->toAiPayload())->all(),
        );

        $replyMessage = $journalConversation->messages()->create([
            'role' => 'assistant',
            'content' => $result->message,
            'tool_call' => $result->toolResults ?: null,
        ]);

        $journalConversation->last_message_at = now();
        $journalConversation->save();

        return ['user' => $userMessage, 'assistant' => $replyMessage];
    }

    private function attachmentPayload(JournalMessageAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'url' => route('admin.journal.attachments.show', $attachment),
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->humanSize(),
            'is_image' => $attachment->isImage(),
        ];
    }

    private function authorizeOwner(Request $request, JournalConversation $journalConversation): void
    {
        abort_unless($journalConversation->user_id === $request->user()->id, 403);
    }
}
