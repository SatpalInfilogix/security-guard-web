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
        Schema::table('guard_rosters', function (Blueprint $table) {
            $table->boolean('late_punch_sent')->default(0)->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guard_rosters', function (Blueprint $table) {
            $table->dropColumn('late_punch_sent');
        });
    }
};
