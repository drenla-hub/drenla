<?php

namespace App\AiAssistant\Providers;

use App\AiAssistant\Contracts\AssistantProvider;
use App\AiAssistant\Data\AssistantRequest;
use App\AiAssistant\Data\ConversationState;
use App\AiAssistant\Data\ProviderResponse;

class NullAssistantProvider implements AssistantProvider
{
    public function respond(AssistantRequest $request, ConversationState $conversation): ProviderResponse
    {
        return new ProviderResponse(
            message: 'AI assistant provider is not configured yet. Wire an OpenAI or Gemini adapter in config/ai_assistant.php to enable live responses.',
            action: 'chat',
            completed: true,
        );
    }
}
