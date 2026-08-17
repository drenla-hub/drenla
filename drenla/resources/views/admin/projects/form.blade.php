@extends('layouts.admin')

@section('title', $project->exists ? 'Edit Project' : 'New Project')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Delivery</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $project->exists ? 'Edit project' : 'New project' }}
            </h1>
        </div>
        <a href="{{ route('admin.projects.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}"
          class="space-y-8">
        @csrf
        @if($project->exists) @method('PUT') @endif

        {{-- Core details --}}
        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Project details</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $project->title) }}" required
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $project->slug) }}"
                           placeholder="auto-generated from title"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Client *</label>
                    <select name="client_id" required
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id', $project->client_id) === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Project brief</label>
                    <select name="proposal_id"
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="">No project brief linked</option>
                        @foreach($proposals as $proposal)
                            <option value="{{ $proposal->id }}" @selected((string) old('proposal_id', $project->proposal_id) === (string) $proposal->id)>{{ $proposal->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Linked invoice</label>
                    <select name="finance_document_id"
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="">No invoice linked</option>
                        @foreach($financeDocuments as $document)
                            <option value="{{ $document->id }}" @selected((string) $linkedFinanceDocumentId === (string) $document->id)>
                                {{ $document->reference_number ?: 'Draft invoice' }} · {{ $document->client?->name ?: 'No client' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($project->exists)
                        <div class="mt-3 flex flex-wrap gap-3 text-[11px] font-semibold uppercase tracking-[0.12em]">
                            <a href="{{ route('admin.finance.create', ['type' => 'invoice', 'project_id' => $project->id]) }}"
                               class="text-[#777] transition hover:text-white">+ Create invoice</a>
                        </div>
                        <p class="mt-2 text-[11px] text-[#555]">The linked invoice powers the commercial value shown on project screens.</p>
                    @else
                        <p class="mt-2 text-[11px] text-[#555]">You can link an existing invoice now. Create a new linked invoice after saving the project.</p>
                    @endif
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Status *</label>
                    <select name="status" required
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="planned"   @selected(old('status', $project->status) === 'planned')>Planned</option>
                        <option value="active"    @selected(old('status', $project->status) === 'active')>Active</option>
                        <option value="blocked"   @selected(old('status', $project->status) === 'blocked')>Blocked</option>
                        <option value="on_hold"   @selected(old('status', $project->status) === 'on_hold')>On hold</option>
                        <option value="completed" @selected(old('status', $project->status) === 'completed')>Completed</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Summary</label>
                    <input type="text" name="summary" value="{{ old('summary', $project->summary) }}"
                           placeholder="One-line description"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Start date</label>
                    <input type="date" name="start_date"
                           value="{{ old('start_date', optional($project->start_date)->toDateString()) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Due date</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', optional($project->due_date)->toDateString()) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Description</label>
                <textarea name="description" rows="8"
                          class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('description', $project->description) }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $project->exists ? 'Update project' : 'Create project' }}
            </button>
            @if ($project->exists)
                <a href="{{ route('admin.projects.show', $project) }}"
                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#555] hover:text-white transition-colors">
                    Cancel
                </a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="inline ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">
                        Delete project
                    </button>
                </form>
            @endif
        </div>
    </form>

</div>
@endsection
