<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rule extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'event_id',
        'title',
        'description',
    ];

    public function getDescriptionAttribute($value)
    {
        return json_decode($value, true);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

}
