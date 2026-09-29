<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_location_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('location_reference');
            $table->decimal('top_left_lat', 10, 7);
            $table->decimal('top_left_lng', 10, 7);
            $table->decimal('top_right_lat', 10, 7);
            $table->decimal('top_right_lng', 10, 7);
            $table->decimal('bottom_right_lat', 10, 7);
            $table->decimal('bottom_right_lng', 10, 7);
            $table->decimal('bottom_left_lat', 10, 7);
            $table->decimal('bottom_left_lng', 10, 7);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_location_areas');
    }
};
