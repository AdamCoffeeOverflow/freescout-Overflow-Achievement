<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'overflowachievement_events';

    protected $fillable = [
        'user_id',
        'event_type',
        'conversation_id',
        'subject_type',
        'subject_id',
        'xp_delta',
        'meta',
    ];

    protected $casts = [
        'user_id' => 'int',
        'conversation_id' => 'int',
        'subject_id' => 'int',
        'xp_delta' => 'int',
        // Stored as JSON string in DB (longText); expose as array in PHP.
        'meta' => 'array',
    ];
}
