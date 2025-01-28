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
        Schema::create('client_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('location_code')->nullable();
            $table->string('location')->nullable();
            $table->string('sector_id')->nullable();
            $table->string('region_code')->nullable();
            $table->string('region')->nullable();
            $table->string('area_code')->nullable();
            $table->string('area')->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('radius', 75)->nullable();
            $table->string('sr_manager')->nullable();
            $table->string('sr_manager_email')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('manager_email')->nullable();
            $table->string('supervisor')->nullable();
            $table->string('supervisor_email')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('unit_no_client')->nullable();
            $table->string('building_name_client')->nullable();
            $table->string('street_no_client')->nullable();
            $table->string('street_road_client')->nullable();
            $table->string('parish_client')->nullable();
            $table->string('country_client')->nullable();
            $table->string('postal_code_client')->nullable();
            $table->string('unit_no_location')->nullable();
            $table->string('building_name_location')->nullable();
            $table->string('street_no_location')->nullable();
            $table->string('street_road_location')->nullable();
            $table->string('parish_location')->nullable();
            $table->string('country_location')->nullable();
            $table->string('postal_code_location')->nullable();
            $table->timestamps();
            
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_sites');
    }
};
