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
        Schema::create('employee_overtimes_main', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_overtime_main_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('employee_overtime_id')->nullable(); // Make this nullable for 'set null'
            $table->decimal('rate', 10, 2);
            $table->date('work_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_overtimes_main');
    }
};
