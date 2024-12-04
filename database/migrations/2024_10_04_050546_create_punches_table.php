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
            $table->timestamps();

            $table->index(['in_time']);
            $table->index(['out_time']);
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
