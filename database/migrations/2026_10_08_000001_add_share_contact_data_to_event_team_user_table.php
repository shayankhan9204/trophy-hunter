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
        Schema::table('event_team_user', function (Blueprint $table) {
            $table->boolean('share_contact_data')->default(false)->after('angular_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_team_user', function (Blueprint $table) {
            $table->dropColumn('share_contact_data');
        });
    }
};
