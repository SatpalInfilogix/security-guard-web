<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardLeaveEncashmentsTable extends Migration
{
    public function up()
    {
        Schema::create('guard_leave_encashments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guard_id');
            $table->integer('encash_leaves');
            $table->integer('pending_leaves')->default(0);
            $table->timestamps();

            $table->foreign('guard_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('guard_leave_encashments');
    }
}
