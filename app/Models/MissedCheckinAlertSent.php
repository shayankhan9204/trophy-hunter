<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissedCheckinAlertSent extends Model
{
    protected $table = 'missed_checkin_alerts_sent';

    protected $fillable = [
        'event_date_id',
        'interval_number',
        'event_contact_id',
        'twilio_message_sid',
    ];

    public function eventDate(): BelongsTo
    {
        return $this->belongsTo(EventDate::class);
    }

    public function eventContact(): BelongsTo
    {
        return $this->belongsTo(EventContact::class);
    }
}
