<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('product');
            $table->integer('quantity');
            $table->string('dosage');
            $table->integer('every')->nullable();
            $table->enum('period', ['hour', 'hours', 'day', 'days', 'week', 'weeks'])->nullable();
            $table->boolean('as_needed')->default(false);
            $table->text('directions');
            $table->timestamps();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreign('patient_id')
                  ->references('id')
                  ->on('patients')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescriptions');
    }
}