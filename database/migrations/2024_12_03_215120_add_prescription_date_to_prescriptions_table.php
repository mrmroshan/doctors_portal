<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->date('prescription_date')->after('patient_id');
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('prescription_date');
        });
    }
};