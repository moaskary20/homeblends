<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_bundles', function (Blueprint $table) {
            if (! Schema::hasColumn('product_bundles', 'short_description')) {
                $table->text('short_description')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('product_bundles', 'description')) {
                $table->longText('description')->nullable()->after('short_description');
            }
            if (! Schema::hasColumn('product_bundles', 'main_image')) {
                $table->string('main_image')->nullable()->after('description');
            }
            if (! Schema::hasColumn('product_bundles', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('bundle_price');
            }
            if (! Schema::hasColumn('product_bundles', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (! Schema::hasColumn('product_bundles', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        Schema::table('bundle_items', function (Blueprint $table) {
            if (! Schema::hasColumn('bundle_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('bundle_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('quantity');
            }
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'product_bundle_id')) {
                $table->foreignId('product_bundle_id')->nullable()->after('product_variant_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('cart_items', 'bundle_snapshot')) {
                $table->json('bundle_snapshot')->nullable()->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'product_bundle_id')) {
                $table->dropForeign(['product_bundle_id']);
                $table->dropColumn(['product_bundle_id', 'bundle_snapshot']);
            }
        });

        Schema::table('bundle_items', function (Blueprint $table) {
            foreach (['product_variant_id', 'sort_order'] as $column) {
                if (Schema::hasColumn('bundle_items', $column)) {
                    if ($column === 'product_variant_id') {
                        $table->dropForeign(['product_variant_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('product_bundles', function (Blueprint $table) {
            foreach (['short_description', 'description', 'main_image', 'starts_at', 'ends_at', 'sort_order'] as $column) {
                if (Schema::hasColumn('product_bundles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
