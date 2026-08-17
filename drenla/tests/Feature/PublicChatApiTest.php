<?php

use App\Models\ChatSession;

beforeEach(function () {
    config(['ai_assistant.default' => 'null']);
});

it('starts a new anonymous chat session', function () {
    $response = $this->postJson('/api/chat/sessions')->assertCreated();

    $response->assertJsonStructure(['session_token', 'ai_enabled', 'messages']);
    expect($response->json('session_token'))->not->toBeEmpty();
    expect($response->json('ai_enabled'))->toBeTrue();
    expect($response->json('messages'))->toBeEmpty();

    expect(ChatSession::where('session_token', $response->json('session_token'))->exists())->toBeTrue();
});

it('resumes a session by token and returns its transcript', function () {
    $session = ChatSession::create(['session_token' => 'resume-me', 'visitor_name' => 'Amara']);
    $session->messages()->create(['role' => 'user', 'content' => 'Hi there']);

    $response = $this->getJson('/api/chat/sessions/resume-me')->assertOk();

    expect($response->json('visitor_name'))->toBe('Amara');
    expect($response->json('messages'))->toHaveCount(1);
});

it('returns 404 for an unknown session token', function () {
    $this->getJson('/api/chat/sessions/does-not-exist')->assertNotFound();
});

it('stores a visitor message and an ai reply when ai_enabled is true', function () {
    $session = ChatSession::create(['session_token' => 'live-session', 'ai_enabled' => true]);

    $response = $this->postJson('/api/chat/sessions/live-session/messages', [
        'message' => 'What services do you offer?',
    ])->assertCreated();

    expect($response->json('message.role'))->toBe('user');
    expect($response->json('reply'))->not->toBeNull();
    expect($response->json('reply.role'))->toBe('assistant');
    expect($session->messages()->count())->toBe(2);
});

it('stores a visitor message with no reply when ai_enabled is false', function () {
    $session = ChatSession::create(['session_token' => 'paused-session', 'ai_enabled' => false]);

    $response = $this->postJson('/api/chat/sessions/paused-session/messages', [
        'message' => 'Is anyone there?',
    ])->assertCreated();

    expect($response->json('reply'))->toBeNull();
    expect($session->messages()->count())->toBe(1);
});

it('never exposes tool_call payloads in the public message shape', function () {
    $session = ChatSession::create(['session_token' => 'tool-session']);
    $session->messages()->create([
        'role' => 'assistant',
        'content' => 'Here you go',
        'tool_call' => [['name' => 'list_projects', 'arguments' => []]],
    ]);

    $response = $this->getJson('/api/chat/sessions/tool-session')->assertOk();

    expect($response->json('messages.0'))->not->toHaveKey('tool_call');
});

it('updates visitor contact fields when provided on a message post', function () {
    $session = ChatSession::create(['session_token' => 'contact-session']);

    $this->postJson('/api/chat/sessions/contact-session/messages', [
        'message' => 'My name is Jane and my email is jane@example.com',
        'visitor' => ['name' => 'Jane', 'email' => 'jane@example.com'],
    ])->assertCreated();

    $session->refresh();
    expect($session->visitor_name)->toBe('Jane');
    expect($session->visitor_email)->toBe('jane@example.com');
});

it('returns only messages after the given id when polling', function () {
    $session = ChatSession::create(['session_token' => 'poll-session']);
    $session->messages()->create(['role' => 'user', 'content' => 'first']);
    $second = $session->messages()->create(['role' => 'assistant', 'content' => 'second']);

    $response = $this->getJson("/api/chat/sessions/poll-session/messages?after_id={$second->id}")->assertOk();

    expect($response->json('messages'))->toBeEmpty();

    $response = $this->getJson('/api/chat/sessions/poll-session/messages?after_id=0')->assertOk();
    expect($response->json('messages'))->toHaveCount(2);
});

it('rate limits repeated message posts from the same ip', function () {
    $session = ChatSession::create(['session_token' => 'throttle-session', 'ai_enabled' => false]);

    for ($i = 0; $i < 20; $i++) {
        $this->postJson('/api/chat/sessions/throttle-session/messages', ['message' => "message {$i}"])
            ->assertCreated();
    }

    $this->postJson('/api/chat/sessions/throttle-session/messages', ['message' => 'one too many'])
        ->assertStatus(429);
});
