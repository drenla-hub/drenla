<?php

use App\Models\Inquiry;
use App\Models\User;

it('adds a relationship note to an inquiry', function () {
    $admin = User::factory()->superAdmin()->create();
    $inquiry = Inquiry::create([
        'name' => 'Chebet Kiplagat',
        'email' => 'chebet@example.com',
        'subject' => 'New build inquiry',
        'message' => 'Looking to start a project next quarter.',
        'status' => 'new',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.inquiries.lead-notes.store', $inquiry), [
            'body' => 'Called back, scheduling a discovery session.',
        ])
        ->assertRedirect(route('admin.inquiries.show', $inquiry));

    $note = $inquiry->leadNotes()->firstOrFail();
    expect($note->body)->toBe('Called back, scheduling a discovery session.');
    expect($note->user_id)->toBe($admin->id);
});

it('shows relationship notes on the inquiry detail screen', function () {
    $admin = User::factory()->superAdmin()->create();
    $inquiry = Inquiry::create([
        'name' => 'Chebet Kiplagat',
        'email' => 'chebet2@example.com',
        'subject' => 'New build inquiry',
        'message' => 'Looking to start a project next quarter.',
        'status' => 'new',
    ]);
    $inquiry->leadNotes()->create(['user_id' => $admin->id, 'body' => 'Discovery session booked.']);

    $this->actingAs($admin)
        ->get(route('admin.inquiries.show', $inquiry))
        ->assertOk()
        ->assertSee('Relationship notes')
        ->assertSee('Discovery session booked.');
});
