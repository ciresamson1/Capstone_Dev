<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'unique_id',
        'project_id',
        'title',
        'description',
        'assigned_to',
        'start_date',
        'end_date',
        'progress',
        'status',
        'comment_email_enabled',
    ];

    protected $casts = [
        'comment_email_enabled' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($task) {
            if (!empty($task->unique_id)) {
                return;
            }

            do {
                $candidate = 'TSK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            } while (static::where('unique_id', $candidate)->exists());

            $task->unique_id = $candidate;
        });
    }

    /**
     * Returns the effective display status.
     * A task is "overdue" when its end_date has passed and it is not completed.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->progress >= 100 || $this->status === 'completed') {
            return 'completed';
        }
        if ($this->end_date && Carbon::parse($this->end_date)->endOfDay()->isPast()) {
            return 'overdue';
        }
        return $this->status ?? 'pending';
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function subTasks()
    {
        return $this->hasMany(SubTask::class)->orderBy('created_at');
    }

   public function comments()
    {
        return $this->hasMany(TaskComment::class)
            ->whereNull('parent_id')
            ->latest();
    }
}