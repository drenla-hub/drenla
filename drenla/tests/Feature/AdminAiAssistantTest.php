<?php

use App\Models\User;

it('requires admin authentication for the ai assistant endpoint', function () {
    $this->postJson('/admin/ai-assistant/respond', [
        'prompt' => 'Summarize current projects.',
    ])->assertUnauthorized();
});

it('returns a structured response for authenticated admins', function () {
    config()->set('ai_assistant.default', 'null');

    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->postJson('/admin/ai-assistant/respond', [
            'prompt' => 'Summarize current projects.',
            'context' => [
                'page' => 'dashboard',
            ],
        ])
        ->assertOk()
        ->assertJson([
            'assistant' => 'admin',
            'provider' => 'null',
            'action' => 'chat',
            'actions' => [],
        ])
        ->assertJsonPath('tool_results', [])
        ->assertJsonPath('message', 'AI assistant provider is not configured yet. Wire an OpenAI or Gemini adapter in config/ai_assistant.php to enable live responses.');
});
