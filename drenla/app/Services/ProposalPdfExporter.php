<?php

namespace App\Services;

use App\Data\ExportedProposalPdf;
use App\Models\Proposal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ProposalPdfExporter
{
    public function export(Proposal $proposal): ExportedProposalPdf
    {
        $directory = storage_path('app/private/proposals');

        // Use a unique chrome profile per export run — avoids lock-file contention
        // if a previous Chrome process crashed without cleaning up.
        $chromeUserData = storage_path('app/private/proposals/chrome-'.Str::random(8));

        File::ensureDirectoryExists($directory);
        File::ensureDirectoryExists($chromeUserData);

        $slug = Str::slug($proposal->reference_number ?: $proposal->title ?: 'proposal');
        $pdfPath = $directory.'/'.$slug.'-'.now()->format('YmdHis').'.pdf';

        // Render straight to an HTML file on disk and point Chrome at file:// rather
        // than an HTTP signed URL fetched from this same app. The print template is
        // already fully self-contained (logo/QR/fonts embedded as base64 data URIs,
        // no external asset requests — see print.blade.php's $embed() closure), so
        // there's nothing an HTTP round-trip buys here. It only cost reliability: in
        // local dev, `php artisan serve`'s single-threaded PHP built-in server
        // deadlocks on this exact pattern — the /export request is still blocked
        // waiting on this process when Chrome tries to fetch the /print URL back
        // from that same busy server, and the export hangs until Symfony's Process
        // timeout kills it 120s later. file:// has no such self-dependency, and
        // sidesteps needing a correct APP_URL/signed-route setup entirely.
        $htmlPath = $directory.'/'.$slug.'-'.now()->format('YmdHis').'-'.Str::random(6).'.html';
        File::put($htmlPath, View::make('admin.proposals.print', [
            'proposal' => $proposal->loadMissing('client'),
            'document' => $proposal->normalized_document_data,
            'quotation' => $proposal->quotation()->with('items', 'client')->first(),
            'previewMode' => false,
        ])->render());

        $process = new Process([
            config('proposals.chrome_path'),
            '--headless=new',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--no-first-run',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-sync',
            '--mute-audio',
            '--hide-scrollbars',
            // NOTE: --run-all-compositor-stages-before-draw is intentionally omitted —
            // it can deadlock Chrome headless during PDF export.
            '--virtual-time-budget=8000',       // 8 s for JS timers / font swaps
            '--print-to-pdf-no-header',         // suppress Chrome's default URL / page-number header-footer
            '--user-data-dir='.$chromeUserData,
            '--print-to-pdf='.$pdfPath,
            'file://'.$htmlPath,
        ]);

        $process->setTimeout(120);

        try {
            $process->run();
        } finally {
            // Always clean up the temp profile directory and rendered HTML, even if
            // run() throws (e.g. ProcessTimedOutException) — previously this sat
            // after run() with no try/finally, so a timeout leaked the whole Chrome
            // profile directory (tens of MB each) on every failed export.
            File::deleteDirectory($chromeUserData);
            File::delete($htmlPath);
        }

        if (! $process->isSuccessful() || ! File::exists($pdfPath)) {
            throw new \RuntimeException(
                'Proposal PDF export failed.'
                .($process->getErrorOutput() ? ' Chrome said: '.trim($process->getErrorOutput()) : '')
            );
        }

        return new ExportedProposalPdf(
            path: $pdfPath,
            filename: ($proposal->reference_number ?: Str::slug($proposal->title ?: 'proposal')).'.pdf',
        );
    }
}
