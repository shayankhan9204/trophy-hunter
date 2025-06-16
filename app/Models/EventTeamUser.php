<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTeamUser extends Model
{
    protected $table = 'event_team_user';
    protected $fillable = [
        'event_id',
        'team_id',
        'user_id',
        'angular_uid',
    ];
}
