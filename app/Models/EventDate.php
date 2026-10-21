<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDate extends Model
{
    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
        'im_safe_interval',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
