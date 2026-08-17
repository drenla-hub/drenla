<?php

use App\Models\Client;
use App\Models\Inquiry;
use App\Models\User;

it('attaches a history of lead notes to a client', function () {
    $client = Client::create(['name' => 'Fatima Noor', 'email' => 'fatima@example.com', 'status' => 'lead']);
    $staff = User::factory()->superAdmin()->create();

    $client->leadNotes()->create(['user_id' => $staff->id, 'body' => 'Initial discovery call went well.']);
    $client->leadNotes()->create(['user_id' => $staff->id, 'body' => 'Sent follow-up proposal.']);

    expect($client->leadNotes()->count())->toBe(2);
    expect($client->leadNotes()->first()->body)->toBe('Sent follow-up proposal.'); // latest-first
    expect($client->leadNotes()->first()->author->is($staff))->toBeTrue();
});

it('attaches a history of lead notes to an inquiry independently of a client', function () {
    $inquiry = Inquiry::create([
        'name' => 'Grace Adhiambo', 'email' => 'grace@example.com', 'message' => 'Interested in a residential project.',
    ]);

    $inquiry->leadNotes()->create(['body' => 'Qualified — has budget.']);

    expect($inquiry->leadNotes()->count())->toBe(1);
});
