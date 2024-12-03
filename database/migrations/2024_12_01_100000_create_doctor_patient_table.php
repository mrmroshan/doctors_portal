<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('doctor_patient', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('doctor_id');
        $table->unsignedBigInteger('patient_id');
        $table->timestamps();

        $table->foreign('doctor_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');
            
        $table->foreign('patient_id')
            ->references('id')
            ->on('patients')
            ->onDelete('cascade');
    });
}

    public function down()
    {
        Schema::dropIfExists('doctor_patient');
    }
};