<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wishlists')
            ->whereNull('user_id')
            ->where(function ($query): void {
                $query->whereNull('session_id')->orWhere('session_id', '');
            })
            ->delete();

        DB::table('compare_lists')
            ->whereNull('user_id')
            ->where(function ($query): void {
                $query->whereNull('session_id')->orWhere('session_id', '');
            })
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
