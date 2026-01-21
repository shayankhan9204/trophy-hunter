<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EventCatch extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'event_id',
        'team_id',
        'angler_id',
        'specie_id',
        'fork_length',
        'tag_type',
        'tag_no',
        'line_class',
        'points',
        'catch_timestamp'
    ];

    public function angler()
    {
        return $this->belongsTo(User::class, 'angler_id', 'id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function specie()
    {
        return $this->belongsTo(Specie::class, 'specie_id', 'id');
    }

}
