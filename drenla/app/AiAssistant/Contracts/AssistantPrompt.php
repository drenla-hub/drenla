<?php

namespace App\AiAssistant\Contracts;

interface AssistantPrompt
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $toolDefinitions
     */
    public function render(array $context, array $toolDefinitions): string;
}
