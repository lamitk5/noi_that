<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $keep = DB::table('reviews')
            ->select('user_id', 'product_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($keep as $row) {
            DB::table('reviews')
                ->where('user_id', $row->user_id)
                ->where('product_id', $row->product_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
