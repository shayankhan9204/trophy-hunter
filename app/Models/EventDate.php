<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDate extends Model
{
    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
    ];
}
