<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('offers')) {
            Schema::create('offers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('banner_image')->nullable();
                $table->json('gallery')->nullable();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at')->index();
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('installment_months')->default(6);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('offer_products')) {
            Schema::create('offer_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('offer_price', 12, 2);
                $table->unsignedInteger('stock_limit')->nullable();
                $table->unsignedInteger('quantity_sold')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['offer_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('installment_contracts')) {
            Schema::create('installment_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('months');
                $table->decimal('total_amount', 12, 2);
                $table->decimal('monthly_amount', 12, 2);
                $table->string('currency', 3)->default('EGP');
                $table->enum('status', ['active', 'completed', 'overdue', 'cancelled'])->default('active');
                $table->json('offer_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('installment_payments')) {
            Schema::create('installment_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('installment_contract_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('sequence');
                $table->date('due_date');
                $table->decimal('amount', 12, 2);
                $table->enum('status', ['pending', 'paid', 'overdue', 'failed'])->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->timestamp('pre_due_reminded_at')->nullable();
                $table->timestamp('due_reminded_at')->nullable();
                $table->timestamp('overdue_reminded_at')->nullable();
                $table->timestamps();
                $table->unique(['installment_contract_id', 'sequence']);
                $table->index(['due_date', 'status']);
            });
        }

        if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'offer_product_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('offer_product_id')->nullable()->after('product_bundle_id')->constrained()->nullOnDelete();
            });
        }

        if (
            Schema::hasTable('cart_items')
            && Schema::hasColumn('cart_items', 'offer_product_id')
            && ! Schema::hasIndex('cart_items', 'cart_items_offer_lookup')
        ) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index(['cart_id', 'product_id', 'product_variant_id', 'offer_product_id'], 'cart_items_offer_lookup');
            });
        }

        if (Schema::hasTable('order_items')) {
            if (! Schema::hasColumn('order_items', 'offer_id')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->foreignId('offer_id')->nullable()->after('product_variant_id')->constrained()->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('order_items', 'offer_product_id')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->foreignId('offer_product_id')->nullable()->after('offer_id')->constrained()->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('order_items', 'offer_snapshot')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->json('offer_snapshot')->nullable()->after('variant_snapshot');
                });
            }
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'installment_payment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('installment_payment_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            });
        }

        if (
            Schema::hasTable('installment_payments')
            && Schema::hasColumn('installment_payments', 'payment_id')
            && ! $this->hasForeignKey('installment_payments', 'installment_payments_payment_id_foreign')
        ) {
            Schema::table('installment_payments', function (Blueprint $table) {
                $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pending','paid','partial','failed','refunded') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'");
        }

        if (Schema::hasTable('installment_payments') && $this->hasForeignKey('installment_payments', 'installment_payments_payment_id_foreign')) {
            Schema::table('installment_payments', function (Blueprint $table) {
                $table->dropForeign(['payment_id']);
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'installment_payment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('installment_payment_id');
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'offer_product_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('offer_id');
                $table->dropConstrainedForeignId('offer_product_id');
                $table->dropColumn('offer_snapshot');
            });
        }

        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'offer_product_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('offer_product_id');
                if (Schema::hasIndex('cart_items', 'cart_items_offer_lookup')) {
                    $table->dropIndex('cart_items_offer_lookup');
                }
                if (! Schema::hasIndex('cart_items', 'cart_item_unique')) {
                    $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_item_unique');
                }
            });
        }

        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_contracts');
        Schema::dropIfExists('offer_products');
        Schema::dropIfExists('offers');
    }

    protected function hasForeignKey(string $table, string $name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (($foreignKey['name'] ?? null) === $name) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();

        return collect(DB::select(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS
             where CONSTRAINT_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ?',
            [$database, $table, $name, 'FOREIGN KEY']
        ))->isNotEmpty();
    }
};
