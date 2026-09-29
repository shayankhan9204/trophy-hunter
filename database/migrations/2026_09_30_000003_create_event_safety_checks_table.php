<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_safety_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('event_teams')->cascadeOnDelete();
            $table->foreignId('angler_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_location_area_id')->constrained('event_location_areas')->cascadeOnDelete();
            $table->string('location_code');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('time_stamp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_safety_checks');
    }
};
