<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'product_id']);
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('session_id', 120)->nullable()->after('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
            $table->index(['session_id']);
        });

        Schema::table('compare_lists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'product_id']);
        });

        Schema::table('compare_lists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('session_id', 120)->nullable()->after('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'product_id']);
            $table->dropUnique(['session_id', 'product_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('compare_lists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'product_id']);
            $table->dropUnique(['session_id', 'product_id']);
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });

        Schema::table('compare_lists', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
        });
    }
};
