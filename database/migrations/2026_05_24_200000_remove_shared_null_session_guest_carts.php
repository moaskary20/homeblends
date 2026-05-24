<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cartIds = DB::table('carts')
            ->whereNull('user_id')
            ->whereNull('session_id')
            ->pluck('id');

        if ($cartIds->isEmpty()) {
            return;
        }

        DB::table('cart_items')->whereIn('cart_id', $cartIds)->delete();
        DB::table('carts')->whereIn('id', $cartIds)->delete();
    }

    public function down(): void
    {
        //
    }
};
