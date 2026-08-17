<?php

use App\Models\ChatSession;
use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

/** A user whose role has access_admin plus exactly the given extra permissions. */
function chatUserWithPermissions(string ...$permissions): User
{
    static $counter = 0;
    $counter++;

    $role = Role::create([
        'slug' => 'chat_test_role_'.$counter,
        'label' => 'Chat Test Role '.$counter,
        'sort_order' => 950 + $counter,
        'permissions' => array_values(array_unique([Permission::ACCESS_ADMIN, ...$permissions])),
    ]);

    return User::factory()->create(['role' => $role->slug]);
}

it('lets a manage_chat admin reply and flips the session to human-handled', function () {
    $admin = chatUserWithPermissions(Permission::MANAGE_CHAT);
    $session = ChatSession::create(['session_token' => 'takeover-1', 'ai_enabled' => true]);
    $session->messages()->create(['role' => 'user', 'content' => 'Hello?']);

    $this->actingAs($admin)
        ->post(route('admin.chat.reply', $session), ['content' => 'Hi, this is the team.'])
        ->assertRedirect(route('admin.chat.show', $session));

    $session->refresh();
    expect($session->ai_enabled)->toBeFalse();

    // ChatSession::messages() already has its own orderBy('id') asc — an added
    // ->latest('id') on the same column is a no-op, so pull the last loaded
    // message instead of re-querying with a conflicting order.
    $reply = $session->messages->last();
    expect($reply->role)->toBe('admin');
    expect($reply->user_id)->toBe($admin->id);
    expect($reply->content)->toBe('Hi, this is the team.');
});

it('lets an admin resume ai handling for a session', function () {
    $admin = chatUserWithPermissions(Permission::MANAGE_CHAT);
    $session = ChatSession::create(['session_token' => 'takeover-2', 'ai_enabled' => false]);

    $this->actingAs($admin)
        ->post(route('admin.chat.resume-ai', $session))
        ->assertRedirect(route('admin.chat.show', $session));

    expect($session->fresh()->ai_enabled)->toBeTrue();
});

it('denies reply and resume-ai actions to a view_chat-only user', function () {
    $viewer = chatUserWithPermissions(Permission::VIEW_CHAT);
    $session = ChatSession::create(['session_token' => 'takeover-3', 'ai_enabled' => true]);

    $this->actingAs($viewer)
        ->post(route('admin.chat.reply', $session), ['content' => 'Sneaking in'])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('admin.chat.resume-ai', $session))
        ->assertForbidden();

    expect($session->fresh()->ai_enabled)->toBeTrue();
    expect($session->messages()->count())->toBe(0);
});

it('shows distinct sender labels for visitor, assistant, and admin messages', function () {
    $admin = chatUserWithPermissions(Permission::MANAGE_CHAT);
    $session = ChatSession::create(['session_token' => 'takeover-4', 'ai_enabled' => false]);
    $session->messages()->create(['role' => 'user', 'content' => 'Visitor says hi']);
    $session->messages()->create(['role' => 'assistant', 'content' => 'AI says hi back']);
    $session->messages()->create(['role' => 'admin', 'user_id' => $admin->id, 'content' => 'Admin steps in']);

    $response = $this->actingAs($admin)
        ->get(route('admin.chat.show', $session))
        ->assertOk();

    $response->assertSee('Visitor');
    $response->assertSee('Assistant');
    $response->assertSee($admin->name);
    $response->assertSee('Human handling');
});

it('exposes a json polling endpoint that returns only messages after a given id', function () {
    $admin = chatUserWithPermissions(Permission::VIEW_CHAT);
    $session = ChatSession::create(['session_token' => 'takeover-5']);
    $first = $session->messages()->create(['role' => 'user', 'content' => 'first']);
    $session->messages()->create(['role' => 'assistant', 'content' => 'second']);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.chat.messages', $session).'?after_id='.$first->id)
        ->assertOk();

    expect($response->json('messages'))->toHaveCount(1);
    expect($response->json('messages.0.content'))->toBe('second');
});
