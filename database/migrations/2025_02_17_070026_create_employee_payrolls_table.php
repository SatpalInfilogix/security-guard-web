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
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('normal_days', 20, 1)->nullable();
            $table->decimal('leave_paid', 20, 1)->nullable();
            $table->decimal('leave_not_paid', 20, 1)->nullable();
            $table->decimal('pending_leave_balance', 20, 1)->nullable();
            $table->decimal('day_salary', 20, 3)->nullable();
            $table->decimal('gross_salary', 20, 3)->nullable();
            $table->decimal('paye', 20, 3)->nullable();
            $table->decimal('education_tax', 20, 3)->nullable();
            $table->decimal('employer_eduction_tax', 20, 3)->nullable();
            $table->decimal('nis', 20, 3)->nullable();
            $table->decimal('employer_contribution_nis_tax', 20, 3)->nullable();
            $table->decimal('statutory_income', 20, 3)->nullable();
            $table->decimal('nht', 20, 3)->nullable();
            $table->decimal('employer_contribution_nht_tax', 20, 3)->nullable();
            $table->decimal('heart', 20, 3)->nullable();
            $table->decimal('staff_loan', 20, 3)->nullable();
            $table->decimal('pending_staff_loan', 20, 3)->nullable();
            $table->decimal('medical_insurance', 20, 3)->nullable();
            $table->decimal('pending_medical_insurance', 20, 3)->nullable();
            $table->decimal('salary_advance', 20, 3)->nullable();
            $table->decimal('pending_salary_advance', 20, 3)->nullable();
            $table->decimal('approved_pension_scheme', 20, 3)->nullable();
            $table->decimal('pending_approved_pension', 20, 3)->nullable();
            $table->decimal('psra', 20, 3)->nullable();
            $table->decimal('pending_psra', 20, 3)->nullable();
            $table->decimal('bank_loan', 20, 3)->nullable();
            $table->decimal('pending_bank_loan', 20, 3)->nullable();
            $table->decimal('garnishment', 20, 3)->nullable();
            $table->decimal('pending_garnishment', 20, 3)->nullable();
            $table->decimal('missing_goods',20, 3)->nullable();
            $table->decimal('pending_missing_goods', 20, 3)->nullable();
            $table->decimal('damaged_goods', 20, 3)->nullable();
            $table->decimal('pending_damaged_goods', 20, 3)->nullable();
            $table->decimal('threshold', 20, 3)->nullable();
            $table->boolean('is_publish')->default(0);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
