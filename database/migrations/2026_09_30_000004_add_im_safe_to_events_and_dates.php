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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('has_im_safe')->default(0)->after('has_grid_map');
        });

        Schema::table('event_dates', function (Blueprint $table) {
            $table->integer('im_safe_interval')->nullable()->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('has_im_safe');
        });

        Schema::table('event_dates', function (Blueprint $table) {
            $table->dropColumn('im_safe_interval');
        });
    }
};
