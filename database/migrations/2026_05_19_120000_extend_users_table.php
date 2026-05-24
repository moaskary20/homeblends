<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('locale', 5)->default('ar')->after('phone');
            $table->string('currency', 3)->default('EGP')->after('locale');
            $table->unsignedInteger('loyalty_points')->default(0)->after('currency');
            $table->unsignedBigInteger('vip_level_id')->nullable()->after('loyalty_points');
            $table->boolean('is_admin')->default(false)->after('vip_level_id');
            $table->string('avatar')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'locale', 'currency', 'loyalty_points', 'vip_level_id', 'is_admin', 'avatar']);
        });
    }
};
