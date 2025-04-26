<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'insurer_name',
        'policy_number',
        'renewal_date',
        'boat_registration',
        'boat_length',
        'boat_maker',
        'boat_color',
        'emergency_contact_name',
        'emergency_contact_number',
    ];
}
