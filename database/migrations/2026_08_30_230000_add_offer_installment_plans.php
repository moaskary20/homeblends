<?php

use App\Models\Offer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offers') && ! Schema::hasColumn('offers', 'installment_plans')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->json('installment_plans')->nullable()->after('installment_months');
            });
        }

        if (Schema::hasTable('offers') && Schema::hasColumn('offers', 'installment_plans')) {
            Offer::query()->each(function (Offer $offer): void {
                if (is_array($offer->installment_plans) && $offer->installment_plans !== []) {
                    return;
                }

                $months = max(2, (int) $offer->installment_months);
                $offer->forceFill([
                    'installment_plans' => [$months],
                ])->saveQuietly();
            });
        }

        if (Schema::hasTable('carts') && ! Schema::hasColumn('carts', 'installment_months')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->unsignedTinyInteger('installment_months')->nullable()->after('coupon_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'installment_months')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->dropColumn('installment_months');
            });
        }

        if (Schema::hasTable('offers') && Schema::hasColumn('offers', 'installment_plans')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropColumn('installment_plans');
            });
        }
    }
};
