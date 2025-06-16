<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSpecie extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'specie_id'
    ];
}
