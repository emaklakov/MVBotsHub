<?php

namespace App\Domain\Queue;

use App\Domain\Queue\Enums\JobLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id', 'name', 'queue', 'payload',
        'attempts', 'status', 'error',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'status' => JobLogStatus::class
    ];

    public function getDurationAttribute(): ?float
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }
        return $this->started_at->diffInMilliseconds($this->finished_at) / 1000;
    }

    public function scopeFailed($query)
    {
        return $query->where('status', JobLogStatus::FAILED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', JobLogStatus::COMPLETED);
    }
}
