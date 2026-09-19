<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offers') && ! Schema::hasColumn('offers', 'down_payment_amount')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->decimal('down_payment_amount', 12, 2)->default(0)->after('installment_plans');
            });
        }

        if (Schema::hasTable('installment_contracts') && ! Schema::hasColumn('installment_contracts', 'down_payment_amount')) {
            Schema::table('installment_contracts', function (Blueprint $table) {
                $table->decimal('down_payment_amount', 12, 2)->default(0)->after('total_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('offers') && Schema::hasColumn('offers', 'down_payment_amount')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropColumn('down_payment_amount');
            });
        }

        if (Schema::hasTable('installment_contracts') && Schema::hasColumn('installment_contracts', 'down_payment_amount')) {
            Schema::table('installment_contracts', function (Blueprint $table) {
                $table->dropColumn('down_payment_amount');
            });
        }
    }
};
