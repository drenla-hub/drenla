<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'proposal_id',
        'created_by',
        'title',
        'slug',
        'status',
        'summary',
        'description',
        'start_date',
        'due_date',
        'progress_percentage',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'progress_percentage' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            if ($project->slug === null || $project->slug === '') {
                $project->slug = Str::slug($project->title);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class)->orderBy('due_date');
    }

    public function financeDocuments()
    {
        return $this->hasMany(FinanceDocument::class);
    }
}
