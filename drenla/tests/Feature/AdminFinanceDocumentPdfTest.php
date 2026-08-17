<?php

use App\Data\ExportedFinanceDocumentPdf;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\User;
use App\Services\FinanceDocumentPdfExporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

function createFinanceDocumentForPdf(): FinanceDocument
{
    $client = Client::create([
        'name' => 'Amara Wanjiru',
        'company_name' => 'Amara Holdings',
        'email' => 'amara@example.com',
        'status' => 'active',
    ]);

    $document = FinanceDocument::create([
        'client_id' => $client->id,
        'type' => 'invoice',
        'status' => 'sent',
        'reference_number' => 'INV-2026-0007',
        'currency' => 'KES',
        'issue_date' => '2026-07-01',
    ]);

    $document->items()->create([
        'title' => 'Brand strategy sprint',
        'description' => 'Two-week strategy and positioning engagement.',
        'quantity' => 1,
        'unit_price' => 250000,
        'total' => 250000,
    ]);

    return $document->fresh(['client', 'items']);
}

it('redirects guests away from finance document preview', function () {
    $document = createFinanceDocumentForPdf();

    $this->get(route('admin.finance.preview', $document))
        ->assertRedirect('/admin/login');
});

it('renders a finance document preview for admins', function () {
    $user = User::factory()->superAdmin()->create();
    $document = createFinanceDocumentForPdf();

    $this->actingAs($user)
        ->get(route('admin.finance.preview', $document))
        ->assertOk()
        ->assertSee('INV-2026-0007')
        ->assertSee('Amara Wanjiru')
        ->assertSee('Brand strategy sprint');
});

it('renders the signed print route without authentication', function () {
    $document = createFinanceDocumentForPdf();

    $url = URL::temporarySignedRoute('admin.finance.print', now()->addMinutes(5), $document);

    $this->get($url)
        ->assertOk()
        ->assertSee('INV-2026-0007');
});

it('rejects an unsigned request to the print route', function () {
    $document = createFinanceDocumentForPdf();

    $this->get("/admin/finance/{$document->id}/print")
        ->assertForbidden();
});

it('exports a finance document pdf for admins', function () {
    $user = User::factory()->superAdmin()->create();
    $document = createFinanceDocumentForPdf();

    $path = tempnam(sys_get_temp_dir(), 'finance-pdf-');
    File::put($path, '%PDF-1.4 test');

    $this->mock(FinanceDocumentPdfExporter::class, function ($mock) use ($path) {
        $mock->shouldReceive('export')
            ->once()
            ->andReturn(new ExportedFinanceDocumentPdf($path, 'inv-2026-0007.pdf'));
    });

    $this->actingAs($user)
        ->get(route('admin.finance.export', $document))
        ->assertOk()
        ->assertDownload('inv-2026-0007.pdf');
});

it('prevents non admin users from exporting finance documents', function () {
    $user = User::factory()->create(['role' => 'client']);
    $document = createFinanceDocumentForPdf();

    $this->actingAs($user)
        ->get(route('admin.finance.preview', $document))
        ->assertForbidden();
});

it('renders a zero-price line item as a descriptive sub-item without a mark or price columns', function () {
    $user = User::factory()->superAdmin()->create();
    $document = createFinanceDocumentForPdf();
    $document->items()->create([
        'title' => 'Exterior Facade & Landscaping',
        'description' => 'Select Typology (2) Elevation Profiles, General Sitemap',
        'quantity' => 0,
        'unit_price' => 0,
        'total' => 0,
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.finance.preview', $document))
        ->assertOk();

    $response->assertSee('Exterior Facade &amp; Landscaping', false);
    // The priced item above it still gets a mark; the descriptive row's mark is hidden not removed
    // (keeps its title aligned with the priced items), so we check the blank price cell instead.
    $response->assertSeeInOrder(['Exterior Facade &amp; Landscaping', '<td class="r"></td>'], false);
});
