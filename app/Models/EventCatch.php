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

    
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        if (!$media) {
            return '';
        }

        // If URL exists in custom_properties, return it
        if ($media->getCustomProperty('url')) {
            return $media->getCustomProperty('url');
        }

        // fallback to default behavior
        return $media->getUrl($conversionName);
    }

    public function getFullUrl(string $conversionName = ''): string
    {
        // if url stored in custom_properties
        if ($this->getCustomProperty('url')) {
            return $this->getCustomProperty('url');
        }

        return parent::getFullUrl($conversionName);
    }

    public function getUrl(string $conversionName = ''): string
    {
        if ($this->getCustomProperty('url')) {
            return $this->getCustomProperty('url');
        }

        return $this->getUrl($conversionName);
    }

}
