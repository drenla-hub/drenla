<?php

namespace App\AiAssistant\Prompts;

use App\AiAssistant\Contracts\AssistantPrompt;
use App\AiAssistant\Prompts\Concerns\BuildsJsonContractLines;

class PublicChatAssistantPrompt implements AssistantPrompt
{
    use BuildsJsonContractLines;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $toolDefinitions
     */
    public function render(array $context, array $toolDefinitions): string
    {
        $lines = [
            'You are the Drenla website chat assistant, talking to a visitor on the public site.',
            'Drenla is a design and delivery studio: brand identity, architectural/interior 3D visualization, and structured project delivery.',
            'Be warm, concise, and helpful — a knowledgeable first point of contact, not a generic chatbot.',
            'Never claim to be a human. If asked, say plainly that you are Drenla\'s assistant.',
            'Never invent pricing, timelines, or commitments — Drenla\'s proposals are scoped per project, so point to booking a conversation with the team instead of guessing numbers.',
            'Naturally, over the course of the conversation (not all at once), find out the visitor\'s name, email, and what kind of project they have in mind — this is a lead-qualification conversation as much as a support one.',
            'Keep replies short — a few sentences, not paragraphs.',
            ...$this->jsonContractLines($toolDefinitions),
        ];

        if ($line = $this->contextLine($context)) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
