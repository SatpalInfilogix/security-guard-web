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
        Schema::create('guard_tax_thresholds', function (Blueprint $table) {
            $table->id();
            $table->decimal('annual', 10, 2);
            $table->decimal('monthly', 10, 2)->nullable();
            $table->decimal('fortnightly', 10, 2);
            $table->date('effective_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guard_tax_thresholds');
    }
};
