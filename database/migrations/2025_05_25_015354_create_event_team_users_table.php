<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id');
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->string('angular_uid')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_team_user');
    }
};
