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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 15)->nullable();
            $table->unsignedBigInteger('client_site_id')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->enum('status', ['Paid', 'Unpaid'])->default('Unpaid');
            $table->timestamps();

            $table->foreign('client_site_id')->references('id')->on('client_sites')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
