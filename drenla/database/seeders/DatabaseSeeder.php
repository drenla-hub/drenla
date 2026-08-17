<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\FocusArea;
use App\Models\HomepageSection;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\Proposal;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ProposalDocumentData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'name' => 'Drenla Admin',
            'email' => 'admin@drenla.test',
            'password' => 'password',
        ]);

        SiteSetting::putValue('site_name', 'Drenla');
        SiteSetting::putValue('contact_email', 'hello@drenla.com');
        SiteSetting::putValue('primary_phone', '+254 700 123 456');
        SiteSetting::putValue('office_location', 'Nairobi, Kenya');
        SiteSetting::putValue('footer_text', 'Drenla aligns brand, built environment, and execution discipline.');
        SiteSetting::putValue('navigation', [
            ['label' => 'About', 'href' => '/about-us'],
            ['label' => 'Capabilities', 'href' => '/capabilities'],
            ['label' => 'Work', 'href' => '/our-work'],
            ['label' => 'Insights', 'href' => '/insights'],
            ['label' => 'Contact', 'href' => '/contact'],
        ]);

        HomepageSection::query()->create([
            'key' => 'hero',
            'eyebrow' => 'Institutional Strategy',
            'title' => 'Drenla builds premium systems for brand, place, and delivery.',
            'body' => 'This backend now owns the canonical homepage content contract for the public frontend.',
            'cta_label' => 'Start an engagement',
            'cta_url' => '/contact',
            'payload' => [
                'highlights' => [
                    'Brand launch systems',
                    'Built environment positioning',
                    'Client-visible delivery operations',
                ],
            ],
            'sort_order' => 1,
            'is_published' => true,
        ]);

        FocusArea::query()->create([
            'title' => 'Market Entry Systems',
            'slug' => 'market-entry-systems',
            'summary' => 'Positioning, launch sequencing, and operational coordination for serious market moves.',
            'body' => 'Drenla combines narrative clarity with execution discipline so launch decisions carry through delivery.',
            'featured' => true,
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        CaseStudy::query()->create([
            'title' => 'Elim Residency Positioning',
            'slug' => 'elim-residency-positioning',
            'client_name' => 'Elim',
            'industry' => 'Real Estate',
            'summary' => 'Brand, place, and sales alignment for a premium residential proposition.',
            'body' => 'This seeded case study exists so the public work API returns live content from day one.',
            'featured' => true,
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Article::query()->create([
            'title' => 'Why premium operations need client-visible milestones',
            'slug' => 'why-premium-operations-need-client-visible-milestones',
            'type' => 'insight',
            'excerpt' => 'Execution trust increases when progress and payment gates are explicit.',
            'body' => 'Client-facing progress visibility changes the quality of delivery conversations.',
            'featured' => true,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Article::query()->create([
            'title' => 'Delivery readiness checklist',
            'slug' => 'delivery-readiness-checklist',
            'type' => 'resource',
            'excerpt' => 'A practical checklist for moving from winning a proposal to running a controlled engagement.',
            'body' => 'Use this as a resource baseline for project setup.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $client = Client::query()->create([
            'name' => 'Elim Residency',
            'company_name' => 'Elim Residency Ltd',
            'email' => 'team@elim.example',
            'phone' => '+254 711 000 001',
            'status' => 'active',
            'portal_access_enabled' => true,
            'portal_access_token' => Str::random(32),
            'notes' => 'Seed client for proposal and project flows.',
        ]);

        Inquiry::query()->create([
            'name' => 'Grace Njoroge',
            'email' => 'grace@example.com',
            'company' => 'Elim Residency',
            'phone' => '+254 722 000 002',
            'subject' => 'Project discovery',
            'message' => 'We need positioning and delivery structure for a new high-end development.',
            'status' => 'qualified',
            'assigned_to' => $admin->id,
            'client_id' => $client->id,
        ]);

        // Keep the full template default sections (Section A / Section B / Terms of
        // Engagement — all 6 block types across them) rather than overwriting with a
        // single placeholder section, so the seeded proposal is a complete, realistic
        // showcase of the document family: cover, every block type, a linked
        // quotation, and an acceptance/signature page — not just a thin stub.
        $proposalDocument = ProposalDocumentData::defaults();
        $proposalDocument['header']['client_name'] = 'Elim Residency';
        $proposalDocument['header']['scope'] = "BRAND POSITIONING\nRESIDENTIAL\nDELIVERY OPERATIONS";
        $proposalDocument['hero']['title'] = "ELIM RESIDENCY\nBRAND AND DELIVERY SYSTEM.";
        $proposalDocument['intro']['left_body'] = 'This proposal defines the strategic, visual, and operational system required to position Elim Residency as a premium built-environment proposition.';
        $proposalDocument['intro']['right_body'] = 'Align brand narrative, visual language, and delivery sequencing so the project moves from concept to controlled execution without disconnect between promise and rollout.';
        $proposalDocument['acceptance'] = [
            'enabled' => true,
            'intro_text' => 'Please read and understand all terms listed before signing below',
            'date_label' => now()->format('M, Y'),
            'signers' => [
                ['role' => 'Client Contact', 'name' => 'Grace Njoroge', 'subtitle' => '[Elim Residency Ltd]', 'phone' => '+254 711 000 001'],
                ['role' => 'Drenla Ventures', 'name' => 'Mr Mbuya Adrian', 'subtitle' => '[Creative Director]', 'phone' => '+254 759 947 183'],
            ],
        ];

        $proposal = Proposal::query()->create([
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'title' => 'Elim Residency Brand and Delivery System',
            'slug' => 'elim-residency-brand-and-delivery-system',
            'status' => 'won',
            'document_status' => 'issued',
            'issue_date' => now()->toDateString(),
            'summary' => $proposalDocument['intro']['left_body'],
            'body' => 'This is a seeded proposal to anchor project creation and future finance flows.',
            'document_data' => $proposalDocument,
            'value' => 1850000,
            'is_client_visible' => true,
            'access_token' => Str::random(32),
        ]);

        // Linked quotation — line items mirror the three project milestones below
        // (450k + 700k + 700k = 1,850,000, matching the proposal's own `value`) so the
        // proposal, quotation, and milestone payment amounts all agree with each
        // other, and the exported proposal PDF's embedded quotation page has real
        // numbers instead of a placeholder.
        $quotation = FinanceDocument::query()->create([
            'client_id' => $client->id,
            'proposal_id' => $proposal->id,
            'type' => 'quotation',
            'status' => 'accepted',
            'reference_number' => 'DNR-'.now()->format('M-').'001',
            'currency' => 'KES',
            'issue_date' => now()->toDateString(),
            'amount_paid' => 450000,
            'payment_terms' => "Milestone 1 - Strategy Alignment: KES 450,000 (paid)\nMilestone 2 - Design Development: KES 700,000 (invoiced)\nMilestone 3 - Launch Readiness: KES 700,000 (due on completion)",
            'payment_info' => "A.C Name: DRENLA VENTURES LIMITED\nA.C No: 1324679069\nCurrency: KES\nSwift Code: KCBLKENX\nClearing Code: 01340\nBranch: KCB Karen Platinum Waterfront Centre",
        ]);

        $quotation->items()->createMany([
            [
                'title' => 'Brand Strategy & Positioning',
                'description' => 'Discovery, competitive audit, and premium residential narrative architecture.',
                'quantity' => 1,
                'unit_price' => 450000,
                'total' => 450000,
            ],
            [
                'title' => 'Design Development & Visual Systems',
                'description' => 'Visual language, spatial storytelling deck, and coordinated design review.',
                'quantity' => 1,
                'unit_price' => 700000,
                'total' => 700000,
            ],
            [
                'title' => 'Launch Readiness & Delivery Handover',
                'description' => 'Final package compilation, rollout support, and completion controls.',
                'quantity' => 1,
                'unit_price' => 700000,
                'total' => 700000,
            ],
        ]);

        $quotation->recalculate()->save();

        $project = Project::query()->create([
            'client_id' => $client->id,
            'proposal_id' => $proposal->id,
            'created_by' => $admin->id,
            'title' => 'Elim Residency Delivery Program',
            'slug' => 'elim-residency-delivery-program',
            'status' => 'active',
            'summary' => 'Internal operating project created from the accepted proposal.',
            'description' => 'Seeded project for milestone, task, and payment-gating workflows.',
            'start_date' => '2026-05-25',
            'due_date' => '2026-08-17',
            'progress_percentage' => 24,
        ]);

        $milestones = collect([
            [
                'title' => 'Strategy Alignment',
                'description' => 'Discovery, positioning, and stakeholder alignment for the delivery program.',
                'status' => 'completed',
                'sort_order' => 1,
                'due_date' => '2026-06-15',
                'payment_required' => true,
                'payment_required_amount' => 450000,
                'payment_status' => 'paid',
            ],
            [
                'title' => 'Design Development',
                'description' => 'Translate strategy into design systems, presentation assets, and coordinated reviews.',
                'status' => 'in_progress',
                'sort_order' => 2,
                'due_date' => '2026-07-20',
                'payment_required' => true,
                'payment_required_amount' => 700000,
                'payment_status' => 'invoiced',
            ],
            [
                'title' => 'Launch Readiness',
                'description' => 'Prepare final package, rollout support, and completion controls.',
                'status' => 'planned',
                'sort_order' => 3,
                'due_date' => '2026-08-17',
                'payment_required' => true,
                'payment_required_amount' => 700000,
                'payment_status' => 'pending',
            ],
        ])->map(fn (array $milestone) => ProjectMilestone::query()->create([
            'project_id' => $project->id,
            ...$milestone,
        ]))->keyBy('title');

        $tasks = [
            [
                'project_milestone_id' => $milestones['Strategy Alignment']->id,
                'title' => 'Kickoff and scope alignment',
                'description' => 'Confirm stakeholder expectations, governance cadence, and delivery sequence.',
                'status' => 'done',
                'priority' => 'high',
                'start_date' => '2026-05-25',
                'due_date' => '2026-05-28',
                'estimated_hours' => 10,
                'completed_at' => '2026-05-28 16:30:00',
            ],
            [
                'project_milestone_id' => $milestones['Strategy Alignment']->id,
                'title' => 'Market and competitor audit',
                'description' => 'Review comparable residential propositions and identify whitespace.',
                'status' => 'done',
                'priority' => 'medium',
                'start_date' => '2026-05-29',
                'due_date' => '2026-06-03',
                'estimated_hours' => 14,
                'completed_at' => '2026-06-03 17:15:00',
            ],
            [
                'project_milestone_id' => $milestones['Strategy Alignment']->id,
                'title' => 'Positioning narrative draft',
                'description' => 'Draft the premium residential narrative and client-facing message architecture.',
                'status' => 'done',
                'priority' => 'high',
                'start_date' => '2026-06-02',
                'due_date' => '2026-06-10',
                'estimated_hours' => 18,
                'completed_at' => '2026-06-10 15:45:00',
            ],
            [
                'project_milestone_id' => $milestones['Strategy Alignment']->id,
                'title' => 'Approval meeting and revisions',
                'description' => 'Consolidate client comments and lock the approved strategy direction.',
                'status' => 'done',
                'priority' => 'medium',
                'start_date' => '2026-06-11',
                'due_date' => '2026-06-15',
                'estimated_hours' => 8,
                'completed_at' => '2026-06-15 14:00:00',
            ],
            [
                'project_milestone_id' => $milestones['Design Development']->id,
                'title' => 'Visual language exploration',
                'description' => 'Develop moodboards, references, and visual direction options.',
                'status' => 'done',
                'priority' => 'medium',
                'start_date' => '2026-06-16',
                'due_date' => '2026-06-23',
                'estimated_hours' => 16,
                'completed_at' => '2026-06-23 18:00:00',
            ],
            [
                'project_milestone_id' => $milestones['Design Development']->id,
                'title' => 'Spatial storytelling deck',
                'description' => 'Assemble the investor-facing deck connecting brand, place, and sales narrative.',
                'status' => 'in_progress',
                'priority' => 'high',
                'start_date' => '2026-06-24',
                'due_date' => '2026-07-03',
                'estimated_hours' => 24,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Design Development']->id,
                'title' => 'Internal review checkpoint',
                'description' => 'Cross-discipline review for coherence across narrative and visual assets.',
                'status' => 'review',
                'priority' => 'medium',
                'start_date' => '2026-07-06',
                'due_date' => '2026-07-10',
                'estimated_hours' => 9,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Design Development']->id,
                'title' => 'Client presentation round',
                'description' => 'Present design development package and capture structured feedback.',
                'status' => 'todo',
                'priority' => 'high',
                'start_date' => '2026-07-13',
                'due_date' => '2026-07-20',
                'estimated_hours' => 12,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Launch Readiness']->id,
                'title' => 'Finalize presentation assets',
                'description' => 'Refine final visuals and export-ready presentation collateral.',
                'status' => 'todo',
                'priority' => 'medium',
                'start_date' => '2026-07-21',
                'due_date' => '2026-07-31',
                'estimated_hours' => 18,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Launch Readiness']->id,
                'title' => 'Delivery governance setup',
                'description' => 'Prepare reporting templates, signoff controls, and launch checklists.',
                'status' => 'todo',
                'priority' => 'medium',
                'start_date' => '2026-08-01',
                'due_date' => '2026-08-08',
                'estimated_hours' => 11,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Launch Readiness']->id,
                'title' => 'Launch readiness review',
                'description' => 'Final readiness meeting covering dependencies, approvals, and rollout timing.',
                'status' => 'todo',
                'priority' => 'high',
                'start_date' => '2026-08-10',
                'due_date' => '2026-08-14',
                'estimated_hours' => 8,
                'completed_at' => null,
            ],
            [
                'project_milestone_id' => $milestones['Launch Readiness']->id,
                'title' => 'Final handoff and closeout',
                'description' => 'Issue the final package and close the project against the agreed scope.',
                'status' => 'todo',
                'priority' => 'high',
                'start_date' => '2026-08-15',
                'due_date' => '2026-08-17',
                'estimated_hours' => 6,
                'completed_at' => null,
            ],
        ];

        foreach ($tasks as $task) {
            ProjectTask::query()->create([
                'project_id' => $project->id,
                'assigned_to' => $admin->id,
                ...$task,
            ]);
        }
    }
}
