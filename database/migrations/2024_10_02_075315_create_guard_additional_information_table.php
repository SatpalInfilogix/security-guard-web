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
        Schema::create('guard_additional_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trn')->nullable();
            $table->string('nis')->nullable();
            $table->string('psra')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_birth')->nullable();
           /*  $table->string('employer_company_name')->nullable();
            $table->string('guards_current_rate')->nullable();
            $table->string('location_code')->nullable();
            $table->string('location_name')->nullable();
            $table->string('client_code')->nullable();
            $table->string('client_name')->nullable(); */
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->unsignedBigInteger('guard_employee_as_id')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->date('date_of_seperation')->nullable();

            $table->timestamps();
            $table->foreign('guard_type_id')->references('id')->on('rate_masters')->onDelete('set null');
            $table->foreign('guard_employee_as_id')->references('id')->on('rate_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guard_additional_information');
    }
};
