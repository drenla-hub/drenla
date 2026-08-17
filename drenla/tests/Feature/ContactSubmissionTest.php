<?php

use App\Models\Inquiry;

it('persists contact submissions', function () {
    $response = $this->postJson('/api/site/contact', [
        'name' => 'Grace Njoroge',
        'email' => 'grace@example.com',
        'company' => 'Elim Residency',
        'phone' => '+254722000001',
        'subject' => 'Project discovery',
        'message' => 'We need a strategic engagement.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', 'new');

    expect(Inquiry::count())->toBe(1);
});

it('validates required contact fields', function () {
    $this->postJson('/api/site/contact', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'message']);
});
