<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_uid',
        'name',
    ];

//    public function anglers()
//    {
//        return $this->hasMany(User::class, 'team_id', 'id');
//    }

    public function anglers()
    {
        return $this->belongsToMany(User::class, 'event_team_user')
            ->withPivot('event_id', 'angular_uid')
            ->withTimestamps();
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_team_user', 'team_id', 'event_id')
            ->using(EventTeamUser::class)
            ->wherePivotNull('deleted_at');
    }

//    public function events()
//    {
//        return $this->belongsToMany(Event::class, 'event_teams');
//    }

    public function users()
    {
        return $this->hasMany(User::class);
    }


}
