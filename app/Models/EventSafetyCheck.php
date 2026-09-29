<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EventLocationArea;

class EventSafetyCheck extends Model
{
    protected $fillable = [
        'event_id',
        'team_id',
        'angler_id',
        'event_location_area_id',
        'location_code',
        'latitude',
        'longitude',
        'time_stamp',
    ];

    protected $casts = [
        'time_stamp' => 'datetime',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function locationArea()
    {
        return $this->belongsTo(EventLocationArea::class, 'event_location_area_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function team()
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }

    public function angler()
    {
        return $this->belongsTo(User::class, 'angler_id');
    }
}
