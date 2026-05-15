<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $products = DB::table('products')
            ->orderBy('id')
            ->get(['id']);

        foreach ($products as $i => $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['sku' => str_pad($i + 1, 6, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        // irreversible — original SKUs were random strings
    }
};
