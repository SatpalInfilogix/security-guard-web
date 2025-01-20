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
        Schema::create('guard_rosters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guard_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('client_site_id')->nullable()->index();
            $table->unsignedBigInteger('guard_type_id')->nullable();
            $table->date('date')->nullable()->index();
            $table->time('start_time')->nullable()->index();
            $table->time('end_time')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->boolean('is_publish')->default(0);
            $table->timestamps();

            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('client_site_id')->references('id')->on('client_sites')->onDelete('cascade');
            $table->foreign('guard_type_id')->references('id')->on('rate_masters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guard_rosters');
    }
};
