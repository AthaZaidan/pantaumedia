<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapeJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'limit',
        'status',
        'scraped_count',
    ];

    protected $casts = [
        'limit' => 'integer',
        'scraped_count' => 'integer',
    ];
}
