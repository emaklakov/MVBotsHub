<?php

declare(strict_types=1);

namespace App\Domain\Bots\Models;

use App\Domain\Bots\Enums\SystemMessageKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'key',
        'translations',
    ];

    protected function casts(): array
    {
        return [
            'key' => SystemMessageKey::class,
            'translations' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
