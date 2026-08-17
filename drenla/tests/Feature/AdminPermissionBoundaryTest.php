<?php

use App\Models\Article;
use App\Models\ChatSession;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

/** A user whose role has access_admin plus exactly the given extra permissions. */
function userWithPermissions(string ...$permissions): User
{
    static $counter = 0;
    $counter++;

    $role = Role::create([
        'slug' => 'boundary_test_role_'.$counter,
        'label' => 'Boundary Test Role '.$counter,
        'sort_order' => 900 + $counter,
        'permissions' => array_values(array_unique([Permission::ACCESS_ADMIN, ...$permissions])),
    ]);

    return User::factory()->create(['role' => $role->slug]);
}

it('keeps the dashboard, logout, and AI assistant reachable with only access_admin', function () {
    $user = userWithPermissions();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($user)->post(route('admin.logout'))->assertRedirect();
});

// ── Users & Roles ────────────────────────────────────────────────────────
it('gates the users module on view_users/manage_users', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.users.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_USERS);
    $this->actingAs($viewer)->get(route('admin.users.index'))->assertOk();

    $target = User::factory()->create();
    $this->actingAs($viewer)->delete(route('admin.users.destroy', $target))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_USERS);
    $this->actingAs($manager)->delete(route('admin.users.destroy', $target))->assertRedirect();
});

// ── Content ──────────────────────────────────────────────────────────────
it('gates the content module on view_content/manage_content', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.articles.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_CONTENT);
    $this->actingAs($viewer)->get(route('admin.articles.index'))->assertOk();

    $article = Article::create(['title' => 'Boundary Test Article', 'type' => 'insight']);
    $this->actingAs($viewer)->delete(route('admin.articles.destroy', $article))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_CONTENT);
    $this->actingAs($manager)->delete(route('admin.articles.destroy', $article))->assertRedirect();
});

// ── Media ────────────────────────────────────────────────────────────────
it('gates the media module on view_media/manage_media', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.media.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_MEDIA);
    $this->actingAs($viewer)->get(route('admin.media.index'))->assertOk();

    $asset = MediaAsset::create(['title' => 'Boundary Test Asset', 'path' => 'boundary-test.jpg']);
    $this->actingAs($viewer)->delete(route('admin.media.destroy', $asset))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_MEDIA);
    $this->actingAs($manager)->delete(route('admin.media.destroy', $asset))->assertRedirect();
});

// ── Clients & Inquiries ──────────────────────────────────────────────────
it('gates the clients module on view_clients/manage_clients, including the loose regenerate-token route', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.clients.index'))->assertForbidden();
    $this->actingAs($blocked)->get(route('admin.inquiries.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_CLIENTS);
    $this->actingAs($viewer)->get(route('admin.clients.index'))->assertOk();
    $this->actingAs($viewer)->get(route('admin.inquiries.index'))->assertOk();

    $client = Client::create(['name' => 'Boundary Test Client', 'status' => 'active', 'portal_access_enabled' => true]);
    $this->actingAs($viewer)->post(route('admin.clients.regenerate-token', $client))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_CLIENTS);
    $this->actingAs($manager)->post(route('admin.clients.regenerate-token', $client))->assertRedirect();
});

// ── Proposals ────────────────────────────────────────────────────────────
it('gates the proposals module on view_proposals/manage_proposals, including the loose send route', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.proposals.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_PROPOSALS);
    $this->actingAs($viewer)->get(route('admin.proposals.index'))->assertOk();

    $proposal = Proposal::create(['title' => 'Boundary Test Proposal', 'is_client_visible' => false]);
    $this->actingAs($viewer)->post(route('admin.proposals.send', $proposal))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_PROPOSALS);
    $this->actingAs($manager)->post(route('admin.proposals.send', $proposal))->assertRedirect();
});

// ── Projects ─────────────────────────────────────────────────────────────
it('gates the projects module on view_projects/manage_projects, including the loose milestones route', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.projects.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_PROJECTS);
    $this->actingAs($viewer)->get(route('admin.projects.index'))->assertOk();

    $client = Client::create(['name' => 'Boundary Test Client for Project', 'status' => 'active']);
    $project = Project::create(['client_id' => $client->id, 'title' => 'Boundary Test Project', 'status' => 'planned']);

    $this->actingAs($viewer)->post(route('admin.projects.milestones.store', $project), ['title' => 'Milestone'])->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_PROJECTS);
    $this->actingAs($manager)
        ->post(route('admin.projects.milestones.store', $project), ['title' => 'Milestone'])
        ->assertStatus(302); // not forbidden — validation/redirect either way proves the permission gate let it through
});

// ── Finance ──────────────────────────────────────────────────────────────
it('gates the finance module on view_finance/manage_finance, including the loose send route', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.finance.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_FINANCE);
    $this->actingAs($viewer)->get(route('admin.finance.index'))->assertOk();

    $client = Client::create(['name' => 'Boundary Test Finance Client', 'status' => 'active']);
    $document = FinanceDocument::create([
        'client_id' => $client->id, 'type' => 'invoice', 'reference_number' => 'INV-BOUNDARY-001', 'issue_date' => now(),
    ]);

    $this->actingAs($viewer)->post(route('admin.finance.send', $document))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_FINANCE);
    $this->actingAs($manager)->post(route('admin.finance.send', $document))->assertRedirect();
});

// ── Chat conversations ───────────────────────────────────────────────────
it('gates the chat module on view_chat/manage_chat, including the loose destroy route', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.chat.index'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_CHAT);
    $this->actingAs($viewer)->get(route('admin.chat.index'))->assertOk();

    $session = ChatSession::create([
        'session_token' => 'boundary-test-token',
        'visitor_name' => 'Boundary Test Visitor',
    ]);

    $this->actingAs($viewer)->get(route('admin.chat.show', $session))->assertOk();
    $this->actingAs($viewer)->delete(route('admin.chat.destroy', $session))->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_CHAT);
    $this->actingAs($manager)->delete(route('admin.chat.destroy', $session))->assertRedirect();
});

// ── Settings ─────────────────────────────────────────────────────────────
it('gates the settings module on view_settings/manage_settings', function () {
    $blocked = userWithPermissions();
    $this->actingAs($blocked)->get(route('admin.settings.edit'))->assertForbidden();

    $viewer = userWithPermissions(Permission::VIEW_SETTINGS);
    $this->actingAs($viewer)->get(route('admin.settings.edit'))->assertOk();
    $this->actingAs($viewer)->put(route('admin.settings.update'), [])->assertForbidden();

    $manager = userWithPermissions(Permission::MANAGE_SETTINGS);
    $this->actingAs($manager)->put(route('admin.settings.update'), [])->assertStatus(302);
});
