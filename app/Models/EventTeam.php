<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTeam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'team_id'
    ];
}
