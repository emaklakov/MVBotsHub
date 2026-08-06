<?php

namespace App\Domain\Flows\Models;

use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Domain\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'schema',
        'status',
        'version_number',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'status' => FlowVersionStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
