<?php

namespace App\AiAssistant\Services;

use App\AiAssistant\Contracts\AssistantProvider;
use App\AiAssistant\Data\AssistantRequest;
use App\AiAssistant\Data\AssistantResult;
use App\AiAssistant\Data\ConversationState;
use RuntimeException;
use Throwable;

class AiAssistantManager
{
    public function __construct(
        protected AssistantProvider $provider,
        protected AssistantToolRegistry $tools,
        protected AssistantPromptRegistry $prompts,
        protected string $providerName = 'null',
    ) {}

    public function respond(AssistantRequest $request): AssistantResult
    {
        $assistantConfig = config("ai_assistant.assistants.{$request->assistant}");

        if (! is_array($assistantConfig)) {
            throw new RuntimeException("Unknown assistant [{$request->assistant}].");
        }

        $toolNames = array_values(array_filter($assistantConfig['tools'] ?? [], 'is_string'));
        $toolDefinitions = $this->tools->definitions($toolNames);
        $promptKey = (string) ($assistantConfig['prompt'] ?? $request->assistant);
        $prompt = $this->prompts->get($promptKey);
        $conversation = ConversationState::start(
            systemPrompt: $prompt->render($request->context, $toolDefinitions),
            history: $request->history,
            prompt: $request->prompt,
            attachments: $request->attachments,
        );

        $message = '';
        $maxTurns = max(1, min($request->maxTurns, 8));

        for ($turn = 0; $turn < $maxTurns; $turn++) {
            $response = $this->provider->respond($request, $conversation);

            if ($response->message !== null && $response->message !== '') {
                $message = $response->message;
            }

            if ($response->toolCalls === []) {
                return new AssistantResult(
                    assistant: $request->assistant,
                    message: $message !== '' ? $message : 'No response returned.',
                    toolResults: $conversation->toolResults,
                    actions: $response->actions,
                    action: $response->action,
                    provider: $this->providerName,
                );
            }

            foreach ($response->toolCalls as $toolCall) {
                try {
                    $tool = $this->tools->get($toolCall->name);
                    $result = $tool->handle($toolCall->arguments);
                } catch (Throwable $exception) {
                    $result = [
                        'error' => $exception->getMessage(),
                    ];
                }

                $conversation->recordToolResult($toolCall->name, $toolCall->arguments, $result);
            }

            if ($response->completed) {
                break;
            }
        }

        return new AssistantResult(
            assistant: $request->assistant,
            message: $message !== '' ? $message : 'Assistant completed without a text response.',
            toolResults: $conversation->toolResults,
            actions: [],
            action: 'chat',
            provider: $this->providerName,
        );
    }
}
