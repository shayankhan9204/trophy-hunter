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
        'date',
        'start_time',
        'end_time',
        'location',
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

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'event_teams');
    }

}
