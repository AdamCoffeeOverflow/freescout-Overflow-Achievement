<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;

class UnlockedAchievement extends Model
{
    protected $table = 'overflowachievement_unlocked';

    protected $fillable = [
        'user_id',
        'achievement_key',
        'unlocked_at',
        'seen_at',
        'quote_id',
        'quote_text',
        'quote_author',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'seen_at' => 'datetime',
    ];
}
