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
        Schema::create('contact_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('apartment_no')->nullable();
            $table->string('building_name')->nullable();
            $table->string('street_name')->nullable();
            $table->string('parish')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('work_phone_number')->nullable();
            $table->string('personal_phone_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_details');
    }
};
