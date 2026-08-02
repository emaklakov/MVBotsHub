<?php

namespace App\Domain\CRM\Models;

use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = ['phone', 'language'];

    public function subscribers()
    {
        return $this->hasMany(BotSubscriber::class, 'people_id');
    }
}
