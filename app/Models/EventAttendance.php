<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'event_id',
        'date',
        'time_in',
        'time_out',
        'time_in_latitude',
        'time_in_longitude',
        'time_out_latitude',
        'time_out_longitude'
    ];
    public function angler()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

}
