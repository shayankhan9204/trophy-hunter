<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventLocationArea extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'location_reference',
        'top_left_lat',
        'top_left_lng',
        'top_right_lat',
        'top_right_lng',
        'bottom_right_lat',
        'bottom_right_lng',
        'bottom_left_lat',
        'bottom_left_lng',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
