<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubTask extends Model
{
    protected $fillable = [
        'task_id',
        'unique_code',
        'title',
        'description',
        'start_date',
        'end_date',
        'is_completed'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    protected static function booted()
    {
        static::saved(function ($subTask) {
            $task = $subTask->task;

            $total = $task->subTasks()->count();
            $completed = $task->subTasks()->where('is_completed', true)->count();

            if ($total > 0) {
                $task->progress = round(($completed / $total) * 100);
                $task->save();
            }
        });
    }
}