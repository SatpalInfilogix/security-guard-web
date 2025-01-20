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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->string('hours_type')->nullable();
            $table->date('date')->nullable();
            $table->integer('no_of_guards')->nullable();
            $table->decimal('total_hours', 12,2)->nullable();
            $table->decimal('rate', 15,2)->nullable();
            $table->decimal('invoice_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('guard_type_id')->references('id')->on('rate_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
