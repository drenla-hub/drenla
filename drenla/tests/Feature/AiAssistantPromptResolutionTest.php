<?php

use App\AiAssistant\Services\AssistantPromptRegistry;
use App\AiAssistant\Services\JournalAssistant;
use App\AiAssistant\Services\PublicChatAssistant;

it('resolves a distinct, persona-appropriate system prompt for each assistant', function () {
    $registry = app(AssistantPromptRegistry::class);

    $admin = $registry->get('admin')->render([], []);
    $publicChat = $registry->get('public_chat')->render([], []);
    $journal = $registry->get('journal')->render([], []);

    expect($admin)->toContain('internal admin assistant');
    expect($admin)->not->toContain('website chat assistant');
    expect($admin)->not->toContain('private journal');

    expect($publicChat)->toContain('Drenla website chat assistant');
    expect($publicChat)->not->toContain('internal admin assistant');
    expect($publicChat)->toContain('Never claim to be a human');

    expect($journal)->toContain('thinking partner');
    expect($journal)->not->toContain('internal admin assistant');
    expect($journal)->not->toContain('website chat assistant');

    // All three still share the same tool-call/JSON-response contract lines.
    foreach ([$admin, $publicChat, $journal] as $prompt) {
        expect($prompt)->toContain('Always return valid JSON matching the required response shape.');
    }
});

it('throws for an unknown prompt key', function () {
    $registry = app(AssistantPromptRegistry::class);

    $registry->get('does_not_exist');
})->throws(InvalidArgumentException::class);

it('routes public_chat and journal assistant calls through their own persona without error', function () {
    config(['ai_assistant.default' => 'null']);

    $publicChatResult = app(PublicChatAssistant::class)->respond('Hello');
    $journalResult = app(JournalAssistant::class)->respond('Thinking about pricing');

    expect($publicChatResult->assistant)->toBe('public_chat');
    expect($journalResult->assistant)->toBe('journal');
});
