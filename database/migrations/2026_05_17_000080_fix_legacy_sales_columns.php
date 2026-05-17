<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Give legacy columns safe defaults so they don't block inserts using the new schema.
        DB::statement("ALTER TABLE sales      MODIFY COLUMN total_amount DECIMAL(15,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE sales      MODIFY COLUMN paid_amount  DECIMAL(15,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE sale_items MODIFY COLUMN total_price  DECIMAL(15,2) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales      MODIFY COLUMN total_amount DECIMAL(15,2) NOT NULL");
        DB::statement("ALTER TABLE sales      MODIFY COLUMN paid_amount  DECIMAL(15,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE sale_items MODIFY COLUMN total_price  DECIMAL(15,2) NOT NULL");
    }
};
