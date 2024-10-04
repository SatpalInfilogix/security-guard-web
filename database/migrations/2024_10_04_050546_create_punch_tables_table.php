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
        Schema::create('punch_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->index();
            $table->timestamp('in_time')->nullable();
            $table->string('in_lat')->nullable();
            $table->string('in_long')->nullable();
            $table->json('in_location')->nullable();
            $table->string('in_image')->nullable();
            $table->timestamp('out_time')->nullable();
            $table->string('out_lat')->nullable();
            $table->string('out_long')->nullable();
            $table->json('out_location')->nullable();
            $table->string('out_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punch_tables');
    }
};
