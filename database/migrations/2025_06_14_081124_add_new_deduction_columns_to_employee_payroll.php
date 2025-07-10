<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            // NCB Loan
            $table->decimal('ncb_loan', 20, 3)->nullable();
            $table->decimal('pending_ncb_loan', 20, 3)->nullable();
            
            // C&WJ Credit Union Loan
            $table->decimal('cwj_credit_union_loan', 20, 3)->nullable();
            $table->decimal('pending_cwj_credit_union_loan', 20, 3)->nullable();
            
            // Edu Com Co-op Loan
            $table->decimal('edu_com_coop_loan', 20, 3)->nullable();
            $table->decimal('pending_edu_com_coop_loan', 20, 3)->nullable();
            
            // NHT Mortgage Loan
            $table->decimal('nht_mortgage_loan', 20, 3)->nullable();
            $table->decimal('pending_nht_mortgage_loan', 20, 3)->nullable();
            
            // Jamaica National Bank Loan
            $table->decimal('jn_bank_loan', 20, 3)->nullable();
            $table->decimal('pending_jn_bank_loan', 20, 3)->nullable();
            
            // Sagicor Bank Loan
            $table->decimal('sagicor_bank_loan', 20, 3)->nullable();
            $table->decimal('pending_sagicor_bank_loan', 20, 3)->nullable();
            
            // Health Insurance
            $table->decimal('health_insurance', 20, 3)->nullable();
            $table->decimal('pending_health_insurance', 20, 3)->nullable();
            
            // Life Insurance
            $table->decimal('life_insurance', 20, 3)->nullable();
            $table->decimal('pending_life_insurance', 20, 3)->nullable();
            
            // Overpayment
            $table->decimal('overpayment', 20, 3)->nullable();
            $table->decimal('pending_overpayment', 20, 3)->nullable();
            
            // Training
            $table->decimal('training', 20, 3)->nullable();
            $table->decimal('pending_training', 20, 3)->nullable();
        });
    }

    public function down()
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'ncb_loan', 'pending_ncb_loan',
                'cwj_credit_union_loan', 'pending_cwj_credit_union_loan',
                'edu_com_coop_loan', 'pending_edu_com_coop_loan',
                'nht_mortgage_loan', 'pending_nht_mortgage_loan',
                'jn_bank_loan', 'pending_jn_bank_loan',
                'sagicor_bank_loan', 'pending_sagicor_bank_loan',
                'health_insurance', 'pending_health_insurance',
                'life_insurance', 'pending_life_insurance',
                'overpayment', 'pending_overpayment',
                'training', 'pending_training'
            ]);
        });
    }
};