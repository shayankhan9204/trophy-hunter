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
        Schema::create('event_catches', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id');
            $table->integer('team_id');
            $table->integer('angler_id');
            $table->integer('specie_id');
            $table->string('fork_length');
            $table->string('tag_type');
            $table->string('tag_no');
            $table->string('line_class');
            $table->string('points');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_catches');
    }
};
