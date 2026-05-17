<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop jewellery-specific columns from products
        Schema::table('products', function (Blueprint $table) {
            $drop = [];
            $cols = Schema::getColumnListing('products');
            $jewellery = [
                'material','weight','karat','color','size','gemstone',
                'gemstone_weight','gemstone_value','gemstone_quality',
                'making_charge_type','making_charge','wastage_percent',
                'carat_id',
            ];
            foreach ($jewellery as $col) {
                if (in_array($col, $cols)) $drop[] = $col;
            }
            if (!empty($drop)) $table->dropColumn($drop);

            if (!in_array('rack_location', $cols)) {
                $table->string('rack_location', 50)->nullable()->after('min_stock_level');
            }
        });

        // Drop jewellery totals from sales
        Schema::table('sales', function (Blueprint $table) {
            $cols = Schema::getColumnListing('sales');
            $drop = [];
            foreach (['gold_value_total','gemstone_value_total','making_charges_total','wastage_total'] as $col) {
                if (in_array($col, $cols)) $drop[] = $col;
            }
            if (!empty($drop)) $table->dropColumn($drop);
        });

        // Drop jewellery fields from sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $cols = Schema::getColumnListing('sale_items');
            $drop = [];
            foreach (['gold_value','gemstone_value','making_charge','wastage_amount'] as $col) {
                if (in_array($col, $cols)) $drop[] = $col;
            }
            if (!empty($drop)) $table->dropColumn($drop);
        });
    }

    public function down(): void
    {
        // Reversibility not needed; this is a one-way conversion
    }
};
