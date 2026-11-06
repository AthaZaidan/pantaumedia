<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'monitored_account_id',
        'platform',
        'author_name',
        'author_handle',
        'content',
        'is_bot',
        'bot_score',
        'sentiment',
        'sentiment_conf',
        'likes',
        'reposts',
        'post_url',
        'posted_at',
        'metadata',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'bot_score' => 'float',
        'sentiment_conf' => 'float',
        'likes' => 'integer',
        'reposts' => 'integer',
        'posted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MonitoredAccount::class, 'monitored_account_id');
    }
}
