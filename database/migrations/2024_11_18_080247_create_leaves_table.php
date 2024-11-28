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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guard_id')->index();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('Pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
