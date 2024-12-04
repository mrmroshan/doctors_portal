<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'product',
                'quantity',
                'dosage',
                'every',
                'period',
                'as_needed',
                'directions',
            ]);
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('product')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('dosage')->nullable();
            $table->integer('every')->nullable();
            $table->string('period')->nullable();
            $table->boolean('as_needed')->default(false);
            $table->text('directions')->nullable();
        });
    }
};