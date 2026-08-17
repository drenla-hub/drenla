<?php

use App\Models\JournalConversation;
use App\Models\User;

beforeEach(function () {
    config(['ai_assistant.default' => 'null']);
});

it('lets an authenticated admin create, list, and open their own journal conversations', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)->get(route('admin.journal.index'))->assertOk();

    $this->actingAs($user)->post(route('admin.journal.store'))->assertRedirect();
    $conversation = JournalConversation::where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)
        ->get(route('admin.journal.show', $conversation))
        ->assertOk();
});

it('prevents one admin from viewing, updating, deleting, or messaging another admins journal conversation', function () {
    $owner = User::factory()->superAdmin()->create();
    $intruder = User::factory()->superAdmin()->create();
    $conversation = JournalConversation::create(['user_id' => $owner->id, 'title' => 'Private thoughts']);

    $this->actingAs($intruder)->get(route('admin.journal.show', $conversation))->assertForbidden();
    $this->actingAs($intruder)->patch(route('admin.journal.update', $conversation), ['title' => 'hijacked'])->assertForbidden();
    $this->actingAs($intruder)->postJson(route('admin.journal.messages.store', $conversation), ['prompt' => 'hi'])->assertForbidden();
    $this->actingAs($intruder)->delete(route('admin.journal.destroy', $conversation))->assertForbidden();

    expect($conversation->fresh()->title)->toBe('Private thoughts');
    expect(JournalConversation::find($conversation->id))->not->toBeNull();
});

it('persists a prompt and an ai reply as journal messages', function () {
    $user = User::factory()->superAdmin()->create();
    $conversation = JournalConversation::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('admin.journal.messages.store', $conversation), ['prompt' => 'What do you think about raising prices?'])
        ->assertOk();

    expect($response->json('message.role'))->toBe('assistant');
    expect($conversation->messages()->count())->toBe(2);
    expect($conversation->messages()->where('role', 'user')->first()->content)->toBe('What do you think about raising prices?');
});

it('auto-titles a new conversation from its first message', function () {
    $user = User::factory()->superAdmin()->create();
    $conversation = JournalConversation::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('admin.journal.messages.store', $conversation), ['prompt' => 'A fresh idea for the studio brand'])
        ->assertOk();

    expect($conversation->fresh()->title)->not->toBeNull();
    expect($conversation->fresh()->title)->toContain('A fresh idea');
});

it('does not overwrite an existing title on later messages', function () {
    $user = User::factory()->superAdmin()->create();
    $conversation = JournalConversation::create(['user_id' => $user->id, 'title' => 'Kept title']);

    $this->actingAs($user)
        ->postJson(route('admin.journal.messages.store', $conversation), ['prompt' => 'second message'])
        ->assertOk();

    expect($conversation->fresh()->title)->toBe('Kept title');
});

it('requires being logged in to reach the journal at all', function () {
    $this->get(route('admin.journal.index'))->assertRedirect(route('admin.login'));
});
