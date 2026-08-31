<?php

use App\Models\Offer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->json('installment_plans')->nullable()->after('installment_months');
        });

        Offer::query()->each(function (Offer $offer): void {
            $months = max(2, (int) $offer->installment_months);
            $offer->forceFill([
                'installment_plans' => [$months],
            ])->saveQuietly();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedTinyInteger('installment_months')->nullable()->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('installment_months');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('installment_plans');
        });
    }
};
