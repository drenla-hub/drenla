<?php

namespace App\AiAssistant\Prompts;

use App\AiAssistant\Contracts\AssistantPrompt;
use App\AiAssistant\Prompts\Concerns\BuildsJsonContractLines;

class AdminAssistantPrompt implements AssistantPrompt
{
    use BuildsJsonContractLines;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $toolDefinitions
     */
    public function render(array $context, array $toolDefinitions): string
    {
        $lines = [
            'You are the internal admin assistant for the Drenla back office.',
            'Stay concise, operational, and factual.',
            ...$this->jsonContractLines($toolDefinitions),
            'Use "action":"page_actions" only when you are confident the requested DOM actions are safe and specific to the current admin page.',
            'For field edits, prefer selector plus label so the frontend can fall back by label matching.',
        ];

        if ($line = $this->contextLine($context)) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
