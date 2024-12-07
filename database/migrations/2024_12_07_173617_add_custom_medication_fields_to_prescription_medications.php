<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescription_medications', function (Blueprint $table) {
            // Add new columns for custom medications
            $table->boolean('is_custom')->default(false)->after('product');
            $table->string('custom_name')->nullable()->after('is_custom');
            $table->string('custom_strength')->nullable()->after('custom_name');
            $table->text('custom_notes')->nullable()->after('custom_strength');
        });
    }

    public function down()
    {
        Schema::table('prescription_medications', function (Blueprint $table) {
            $table->dropColumn([
                'is_custom',
                'custom_name',
                'custom_strength',
                'custom_notes'
            ]);
        });
    }
};