<?php

namespace App\AiAssistant\Providers;

use App\AiAssistant\Contracts\AssistantProvider;
use App\AiAssistant\Data\AssistantMessage;
use App\AiAssistant\Data\AssistantRequest;
use App\AiAssistant\Data\ConversationState;
use App\AiAssistant\Data\ProviderResponse;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAssistantProvider implements AssistantProvider
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {}

    public function respond(AssistantRequest $request, ConversationState $conversation): ProviderResponse
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Gemini assistant provider is missing an API key.');
        }

        $instructions = [
            $conversation->systemPrompt,
            $conversation->systemPrompt."\nAlways include a non-empty \"message\" string, even for greetings or short acknowledgements.",
        ];

        foreach ($instructions as $instruction) {
            $decoded = $this->requestResponse($conversation->messages, $instruction);

            if (is_array($decoded) && ($decoded['message'] ?? '') !== '') {
                return ProviderResponse::fromArray($decoded);
            }
        }

        return new ProviderResponse(
            message: 'I am here. Tell me what you want to do on this admin page.',
            completed: true,
        );
    }

    /**
     * @param  array<int, AssistantMessage>  $messages
     * @return array<int, array<string, mixed>>
     */
    protected function buildContents(array $messages): array
    {
        return array_map(function (AssistantMessage $message): array {
            $role = $message->role === 'assistant' ? 'model' : 'user';

            $parts = [];

            if ($message->content !== '') {
                $parts[] = ['text' => $message->content];
            }

            foreach ($message->attachments as $attachment) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $attachment['mime_type'],
                        'data' => $attachment['data'],
                    ],
                ];
            }

            if ($parts === []) {
                $parts[] = ['text' => ''];
            }

            return [
                'role' => $role,
                'parts' => $parts,
            ];
        }, $messages);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractText(array $payload): string
    {
        $candidates = $payload['candidates'] ?? [];

        foreach ($candidates as $candidate) {
            foreach (($candidate['content']['parts'] ?? []) as $part) {
                if (is_string($part['text'] ?? null)) {
                    return trim($part['text']);
                }
            }
        }

        return '';
    }

    /**
     * @param  array<int, AssistantMessage>  $messages
     * @return array<string, mixed>
     */
    protected function requestResponse(array $messages, string $systemInstruction): array
    {
        $response = Http::timeout(45)
            ->acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
            ])
            ->post(sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                $this->model
            ), [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemInstruction],
                    ],
                ],
                'contents' => $this->buildContents($messages),
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.2,
                    'maxOutputTokens' => 4096,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $payload = $response->json();
        $text = $this->extractText($payload);

        if ($text === '') {
            throw new RuntimeException('Gemini returned an empty response body.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON: '.$text);
        }

        return $decoded;
    }
}
