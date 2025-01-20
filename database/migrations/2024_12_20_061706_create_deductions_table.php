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
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guard_id');
            $table->string('type');
            $table->decimal('amount', 10, 2)->nullable();
            $table->integer('no_of_payroll');
            $table->date('document_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('one_installment',15, 2)->nullable();
            $table->decimal('pending_balance',15, 2)->nullable();

            $table->timestamps();
            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
