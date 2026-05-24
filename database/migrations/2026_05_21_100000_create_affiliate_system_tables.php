<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('display_name');
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('website')->nullable();
            $table->text('bio')->nullable();
            $table->json('payment_details')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->unsignedInteger('total_clicks')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('landing_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'created_at']);
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('affiliate_click_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('order_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'status']);
        });

        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'affiliate_id')) {
                $table->foreignId('affiliate_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'affiliate_click_id')) {
                $table->foreignId('affiliate_click_id')->nullable()->after('affiliate_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'affiliate_click_id')) {
                $table->dropForeign(['affiliate_click_id']);
                $table->dropColumn('affiliate_click_id');
            }
            if (Schema::hasColumn('orders', 'affiliate_id')) {
                $table->dropForeign(['affiliate_id']);
                $table->dropColumn('affiliate_id');
            }
        });

        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('affiliates');
    }
};
