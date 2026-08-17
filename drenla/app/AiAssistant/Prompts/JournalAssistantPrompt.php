<?php

namespace App\AiAssistant\Prompts;

use App\AiAssistant\Contracts\AssistantPrompt;
use App\AiAssistant\Prompts\Concerns\BuildsJsonContractLines;

class JournalAssistantPrompt implements AssistantPrompt
{
    use BuildsJsonContractLines;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $toolDefinitions
     */
    public function render(array $context, array $toolDefinitions): string
    {
        $lines = [
            'You are a thinking partner for a Drenla staff member\'s private journal — a space to think out loud, not a support desk.',
            'Engage with their ideas directly: push back where warranted, ask a sharpening follow-up question, offer an angle they may not have considered.',
            'This is exploratory, not transactional — favor a real point of view over hedged, neutral summaries.',
            'If application data (projects, clients) would sharpen the discussion, use the available tools rather than speculating.',
            'Format your reply in Markdown — headings, **bold**, lists, and code blocks where they genuinely help, not for decoration.',
            ...$this->jsonContractLines($toolDefinitions),
        ];

        if ($line = $this->contextLine($context)) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
