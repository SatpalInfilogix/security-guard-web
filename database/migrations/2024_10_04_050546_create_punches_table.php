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
        Schema::create('punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->index();
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->unsignedBigInteger('client_site_id')->nullable();
            $table->timestamp('in_time')->nullable();
            $table->decimal('in_lat', 10, 8)->nullable();
            $table->decimal('in_long', 11, 8)->nullable();
            $table->json('in_location')->nullable();
            $table->string('in_image')->nullable();
            $table->timestamp('out_time')->nullable();
            $table->decimal('out_lat', 10, 8)->nullable();
            $table->decimal('out_long', 11, 8)->nullable();
            $table->json('out_location')->nullable();
            $table->string('out_image')->nullable();

            $table->decimal('regular_rate')->nullable();
            $table->decimal('laundry_allowance')->nullable();
            $table->decimal('canine_premium')->nullable();
            $table->decimal('fire_arm_premium')->nullable();
            $table->decimal('gross_hourly_rate')->nullable();
            $table->decimal('overtime_rate')->nullable();
            $table->decimal('holiday_rate')->nullable();
            $table->timestamps();

            $table->index(['in_time']);
            $table->index(['out_time']);

            $table->foreign('client_site_id')->references('id')->on('client_sites')->onDelete('set null');
            $table->foreign('guard_type_id')->references('id')->on('rate_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punches');
    }
};
