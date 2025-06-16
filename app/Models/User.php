<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens , InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'angular_uid',
        'team_id',
        'category',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['profile_picture'];

//    protected static function booted()
//    {
//        static::created(function (User $user) {
//            $user->updateQuietly([
//                'angular_uid' => 'AGL-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
//            ]);
//        });
//    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAngularUidForEventTeam($eventId, $teamId)
    {
        return $this->team()
            ->wherePivot('event_id', $eventId)
            ->wherePivot('team_id', $teamId)
            ->first()
            ->pivot
            ->angular_uid ?? null;
    }

    public function team()
    {
        return $this->belongsToMany(Team::class, 'event_team_user')
            ->withPivot('event_id', 'angular_uid')
            ->withTimestamps();
    }

//    public function team()
//    {
//        return $this->belongsTo(Team::class);
//    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function getProfilePictureAttribute()
    {
        $url = $this->getFirstMediaUrl('profile_picture');

        return $url ?: asset('images/avatar-placeholder.png');
    }

}
