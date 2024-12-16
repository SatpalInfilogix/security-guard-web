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
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->string('normal_hours')->nullable();
            $table->string('overtime')->nullable();
            $table->string('public_holiday')->nullable();
            $table->timestamps();
            
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
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
