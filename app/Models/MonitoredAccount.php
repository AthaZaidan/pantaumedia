<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'handle',
        'name',
        'platform',
        'age',
        'followers',
        'following',
        'posting_frequency',
        'is_bot',
        'bot_score',
        'reasons',
        'topics',
        'cluster',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'bot_score' => 'float',
        'followers' => 'integer',
        'following' => 'integer',
        'reasons' => 'array',
        'topics' => 'array',
        'cluster' => 'array',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }
}
