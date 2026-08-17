<?php

namespace App\AiAssistant\Prompts\Concerns;

/**
 * The tool-call/JSON-response contract every assistant persona shares —
 * factored out so each Prompt class only has to write its own persona/tone
 * lines, not repeat the shape the provider adapter expects.
 */
trait BuildsJsonContractLines
{
    /** @param  array<int, array<string, mixed>>  $toolDefinitions */
    protected function jsonContractLines(array $toolDefinitions): array
    {
        $toolNames = array_map(
            static fn (array $tool): string => (string) ($tool['name'] ?? 'tool'),
            $toolDefinitions
        );

        $lines = [
            'Always return valid JSON matching the required response shape.',
            'If a request depends on application data, prefer a tool call over guessing.',
            'When using tools, return structured tool calls only for the configured tool names.',
            'If you need application data first, return tool_calls and set completed to false.',
            'If you can answer directly, return no tool_calls and set completed to true.',
        ];

        if ($toolNames !== []) {
            $lines[] = 'Available tools: '.implode(', ', $toolNames).'.';
        }

        return $lines;
    }

    /** @param  array<string, mixed>  $context */
    protected function contextLine(array $context): ?string
    {
        return $context !== [] ? 'Request context: '.json_encode($context, JSON_UNESCAPED_SLASHES) : null;
    }
}
