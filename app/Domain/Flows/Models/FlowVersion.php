<?php

namespace App\Domain\Flows\Models;

use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function getStartBlockId(): ?string
    {
        return $this->schema['start_block_id'] ?? null;
    }

    public function getBlock(string $blockId): ?array
    {
        return $this->schema['blocks'][$blockId] ?? null;
    }
}
