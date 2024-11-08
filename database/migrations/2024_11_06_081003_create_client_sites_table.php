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
            $table->string('parish')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('vanguard_manager')->nullable();
            $table->string('contact_operation')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('invoice_recipient_main')->nullable();
            $table->string('invoice_recipient_copy')->nullable();
            $table->string('account_payable_contact_name')->nullable();
            $table->string('email_2')->nullable();
            $table->string('number')->nullable();
            $table->string('number_2')->nullable();
            $table->string('account_payable_contact_email')->nullable();
            $table->string('email_3')->nullable();
            $table->string('telephone_number_2')->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('radius', 75)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
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
