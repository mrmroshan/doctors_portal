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
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'odoo_partner_id')) {
                $table->unsignedBigInteger('odoo_partner_id')->nullable();
            }
            if (!Schema::hasColumn('patients', 'sync_status')) {
                $table->string('sync_status')->nullable();
            }
            if (!Schema::hasColumn('patients', 'sync_error')) {
                $table->text('sync_error')->nullable();
            }
            if (!Schema::hasColumn('patients', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'odoo_partner_id',
                'sync_status',
                'sync_error',
                'last_synced_at'
            ]);
        });
    }
};