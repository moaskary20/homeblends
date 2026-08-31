<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'fulfillment_status')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('fulfillment_status', 32)->default('pending')->after('total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'fulfillment_status')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('fulfillment_status');
            });
        }
    }
};
