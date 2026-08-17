<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

function chatAdminUser(): User
{
    $role = Role::firstOrCreate(
        ['slug' => 'chat_test_admin'],
        ['label' => 'Chat Test Admin', 'sort_order' => 950, 'permissions' => [Permission::ACCESS_ADMIN, Permission::MANAGE_CHAT]]
    );

    return User::factory()->create(['role' => $role->slug]);
}

it('lists chat sessions with message counts, most recently active first', function () {
    $admin = chatAdminUser();

    $older = ChatSession::create(['session_token' => 'older', 'visitor_name' => 'Older Visitor', 'last_message_at' => now()->subDay()]);
    ChatMessage::create(['chat_session_id' => $older->id, 'role' => 'user', 'content' => 'Hi']);

    $newer = ChatSession::create(['session_token' => 'newer', 'visitor_name' => 'Newer Visitor', 'last_message_at' => now()]);
    ChatMessage::create(['chat_session_id' => $newer->id, 'role' => 'user', 'content' => 'Hello']);
    ChatMessage::create(['chat_session_id' => $newer->id, 'role' => 'assistant', 'content' => 'Hi there']);

    $response = $this->actingAs($admin)->get(route('admin.chat.index'))->assertOk();

    $response->assertSeeInOrder(['Newer Visitor', 'Older Visitor']);
});

it('shows the full transcript in order on the session detail page', function () {
    $admin = chatAdminUser();

    $session = ChatSession::create(['session_token' => 'transcript-test', 'visitor_name' => 'Transcript Visitor']);
    ChatMessage::create(['chat_session_id' => $session->id, 'role' => 'user', 'content' => 'First message']);
    ChatMessage::create(['chat_session_id' => $session->id, 'role' => 'assistant', 'content' => 'Second message']);

    $this->actingAs($admin)->get(route('admin.chat.show', $session))
        ->assertOk()
        ->assertSeeInOrder(['First message', 'Second message']);
});

it('deletes a chat session and its messages together', function () {
    $admin = chatAdminUser();

    $session = ChatSession::create(['session_token' => 'delete-test']);
    ChatMessage::create(['chat_session_id' => $session->id, 'role' => 'user', 'content' => 'To be deleted']);

    $this->actingAs($admin)->delete(route('admin.chat.destroy', $session))->assertRedirect(route('admin.chat.index'));

    expect(ChatSession::find($session->id))->toBeNull();
    expect(ChatMessage::where('chat_session_id', $session->id)->count())->toBe(0);
});
