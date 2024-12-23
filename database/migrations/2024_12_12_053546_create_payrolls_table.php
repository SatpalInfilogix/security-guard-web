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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guard_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('normal_hours', 10, 2)->nullable();
            $table->decimal('overtime', 10, 2)->nullable();
            $table->decimal('public_holidays', 10, 2)->nullable();
            $table->decimal('normal_hours_rate', 10, 2)->nullable();
            $table->decimal('overtime_rate', 10, 2)->nullable();
            $table->decimal('public_holiday_rate', 10, 2)->nullable();
            $table->decimal('gross_salary_earned', 15, 2)->nullable();
            $table->decimal('less_nis', 10, 2)->nullable();
            $table->decimal('employer_contribution_nis_tax', 10, 2)->nullable();
            $table->decimal('approved_pension_scheme', 10, 2)->nullable();
            $table->decimal('statutory_income', 15, 2)->nullable();
            $table->decimal('education_tax', 10, 2)->nullable();
            $table->decimal('employer_eduction_tax', 10, 2)->nullable();
            $table->decimal('nht', 10, 2)->nullable();
            $table->decimal('employer_contribution_nht_tax', 10, 2)->nullable();
            $table->decimal('heart', 10, 2)->nullable();
            $table->decimal('paye', 10, 2)->nullable();
            $table->decimal('staff_loan', 10, 2)->nullable();
            $table->decimal('medical_insurance', 10, 2)->nullable();
            $table->decimal('threshold', 10, 2)->nullable();
            $table->boolean('is_publish')->default(0);
            $table->timestamps();

            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
