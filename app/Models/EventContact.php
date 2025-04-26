<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

}
