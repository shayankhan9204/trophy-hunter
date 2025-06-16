<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specie extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'formula',
        'validation_rule',
        'min_validation_rule'
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_species');
    }

}
