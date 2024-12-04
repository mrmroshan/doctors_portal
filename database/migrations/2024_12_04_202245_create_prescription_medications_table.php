<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prescription_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->string('product');
            $table->integer('quantity');
            $table->string('dosage');
            $table->integer('every')->nullable();
            $table->string('period')->nullable();
            $table->boolean('as_needed')->default(false);
            $table->text('directions');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescription_medications');
    }
};