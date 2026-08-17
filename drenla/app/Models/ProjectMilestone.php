<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'sort_order',
        'due_date',
        'payment_required',
        'payment_required_amount',
        'payment_status',
        'blocked_reason',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'payment_required' => 'boolean',
            'payment_required_amount' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }
}
