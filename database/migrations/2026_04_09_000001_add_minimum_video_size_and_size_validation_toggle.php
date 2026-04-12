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
        Schema::table('species', function (Blueprint $table) {
            $table->unsignedInteger('minimum_video_size')->nullable()->after('min_validation_rule');
        });

        Schema::table('event_species', function (Blueprint $table) {
            $table->boolean('is_size_validation_enabled')->default(true)->after('specie_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_species', function (Blueprint $table) {
            $table->dropColumn('is_size_validation_enabled');
        });

        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn('minimum_video_size');
        });
    }
};
