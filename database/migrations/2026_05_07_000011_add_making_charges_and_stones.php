<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration previously added gold/jewelry-specific columns. For spare parts, these are not needed.
        // If you need to add spare part-specific columns, do so here. Otherwise, leave this migration empty or remove it.
        // Example: Add a 'warranty_period' column to products if needed.
        // Schema::table('products', function (Blueprint $table) {
        //     $table->integer('warranty_period_months')->nullable()->after('price');
        // });
        // No changes needed for sale_items or sales tables for spare parts context.
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['making_charge_type','making_charge','wastage_percent','gemstone_weight','gemstone_value','gemstone_quality']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['gold_value','gemstone_value','making_charge','wastage_amount']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['gold_value_total','gemstone_value_total','making_charges_total','wastage_total','tax_rate']);
        });
    }
};
