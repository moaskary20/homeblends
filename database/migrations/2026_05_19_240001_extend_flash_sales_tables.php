<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('flash_sales', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('flash_sales', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('flash_sales', 'banner_image')) {
                $table->string('banner_image')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('flash_sales', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('banner_image');
            }
        });

        Schema::table('flash_sale_products', function (Blueprint $table) {
            if (! Schema::hasColumn('flash_sale_products', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('flash_sale_products', 'quantity_sold')) {
                $table->unsignedInteger('quantity_sold')->default(0)->after('stock_limit');
            }
            if (! Schema::hasColumn('flash_sale_products', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
            if (! Schema::hasColumn('flash_sale_products', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('flash_sale_products', function (Blueprint $table) {
            $columns = ['product_variant_id', 'quantity_sold', 'sort_order', 'created_at', 'updated_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('flash_sale_products', $column)) {
                    if ($column === 'product_variant_id') {
                        $table->dropForeign(['product_variant_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('flash_sales', function (Blueprint $table) {
            foreach (['slug', 'description', 'banner_image', 'sort_order'] as $column) {
                if (Schema::hasColumn('flash_sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
