<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('angular_uid')->nullable()->after('id');
            $table->foreignId('team_id')->nullable()->after('remember_token');
            $table->enum('category', ['adult', 'junior'])->default('adult')->after('team_id');
            $table->string('phone')->nullable()->after('category');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['angular_uid', 'team_id', 'category', 'phone']);
        });

    }

};
