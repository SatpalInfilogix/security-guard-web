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
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('guard_id');
            $table->date('date')->nullable();
            $table->unsignedBigInteger('client_site_id')->nullable();
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->decimal('normal_hours', 10, 2)->nullable();
            $table->decimal('overtime', 10, 2)->nullable();
            $table->decimal('public_holiday', 10, 2)->nullable();
            $table->decimal('normal_hours_rate', 10, 2)->nullable();
            $table->decimal('overtime_rate', 10, 2)->nullable();
            $table->decimal('public_holiday_rate', 10, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_site_id')->references('id')->on('client_sites')->onDelete('set null');
            $table->foreign('guard_type_id')->references('id')->on('rate_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
