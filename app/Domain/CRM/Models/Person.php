<?php

namespace App\Domain\CRM\Models;

use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Users\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory, LogsUserActivity;

    protected static function logLabel(): string
    {
        return 'Человек';
    }

    protected $fillable = ['phone', 'language'];

    public function subscribers()
    {
        return $this->hasMany(BotSubscriber::class, 'person_id');
    }
}
