<?php

namespace App\AiAssistant\Contracts;

use App\AiAssistant\Data\AssistantRequest;
use App\AiAssistant\Data\ConversationState;
use App\AiAssistant\Data\ProviderResponse;

interface AssistantProvider
{
    public function respond(AssistantRequest $request, ConversationState $conversation): ProviderResponse;
}
