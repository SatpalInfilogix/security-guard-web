<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('previous_role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->foreignId('current_role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->date('promotion_date')->nullable();
            $table->text('promotion_remarks')->nullable();
            
            $table->index(['promotion_date']);
            $table->index(['current_role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['previous_role_id']);
            $table->dropForeign(['current_role_id']);
            $table->dropColumn(['previous_role_id', 'current_role_id', 'promotion_date', 'promotion_remarks']);
        });
    }
};