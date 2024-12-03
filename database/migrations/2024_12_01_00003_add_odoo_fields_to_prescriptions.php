<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('odoo_order_id')->nullable()->after('directions');
            $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending')->after('odoo_order_id');
            $table->timestamp('sync_attempted_at')->nullable()->after('sync_status');
            $table->text('sync_error')->nullable()->after('sync_attempted_at');
            $table->foreignId('created_by')->constrained('users')->after('patient_id');
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'odoo_order_id',
                'sync_status',
                'sync_attempted_at',
                'sync_error',
                'created_by'
            ]);
        });
    }
};