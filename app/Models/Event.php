<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends Model implements HasMedia
{
    use  InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
//        'date',
//        'start_time',
//        'end_time',
        'location',
        'fish_bag_size',
        'minimum_release_size',
        'is_tagged',
    ];

    public function getSponsorImages(): array
    {
        return $this->getMedia('sponsors')->map(fn($media) => $media->getUrl())->toArray();
    }
    public function contacts()
    {
        return $this->hasMany(EventContact::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function rules()
    {
        return $this->hasMany(Rule::class);
    }

    public function catches()
    {
        return $this->hasMany(EventCatch::class);
    }
    public function dates()
    {
        return $this->hasMany(EventDate::class);
    }
//    public function teams()
//    {
//        return $this->belongsToMany(Team::class, 'event_teams');
//    }
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'event_team_user')
            ->withPivot('user_id', 'angular_uid')
            ->withTimestamps();
    }

    public function species()
    {
        return $this->belongsToMany(Specie::class, 'event_species');
    }

}
