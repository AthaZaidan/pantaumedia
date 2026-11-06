<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'rank',
        'total_posts',
        'human_pct',
        'bot_pct',
        'pos_pct',
        'neg_pct',
        'trend',
        'keywords',
        'platforms',
    ];

    protected $casts = [
        'rank' => 'integer',
        'total_posts' => 'integer',
        'human_pct' => 'float',
        'bot_pct' => 'float',
        'pos_pct' => 'float',
        'neg_pct' => 'float',
        'keywords' => 'array',
        'platforms' => 'array',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }
}
