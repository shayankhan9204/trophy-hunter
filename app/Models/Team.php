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

    public function anglers()
    {
        return $this->hasMany(User::class, 'team_id', 'id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_teams');
    }

}
