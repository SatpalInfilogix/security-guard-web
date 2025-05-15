<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_rate_masters', function (Blueprint $table) {
            $table->decimal('employee_allowance', 12, 2)->default(0)->nullable()->after('monthly_income');
            $table->decimal('daily_income', 12, 2)->nullable()->after('employee_allowance');
            $table->decimal('hourly_income', 12, 2)->nullable()->after('daily_income');
        });
    }

    public function down(): void
    {
        Schema::table('employee_rate_masters', function (Blueprint $table) {
            $table->dropColumn(['employee_allowance', 'daily_income', 'hourly_income']);
        });
    }
};
