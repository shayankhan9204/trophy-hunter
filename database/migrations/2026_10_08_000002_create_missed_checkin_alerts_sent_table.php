<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which (interval, contact) pairs have already received a missed check-in SMS.
     * A unique index on (event_date_id, interval_number, event_contact_id) makes the
     * alert idempotent: even if the job retries or two workers race, each contact only
     * ever receives one message per interval.
     */
    public function up(): void
    {
        Schema::create('missed_checkin_alerts_sent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_date_id')->constrained('event_dates')->cascadeOnDelete();
            $table->unsignedSmallInteger('interval_number');
            $table->foreignId('event_contact_id')->constrained('event_contacts')->cascadeOnDelete();
            $table->string('twilio_message_sid')->nullable();
            $table->timestamps();

            $table->unique(
                ['event_date_id', 'interval_number', 'event_contact_id'],
                'missed_alert_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_checkin_alerts_sent');
    }
};
