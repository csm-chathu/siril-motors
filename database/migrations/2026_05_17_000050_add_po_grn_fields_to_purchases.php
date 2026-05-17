<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_ref', 100)->nullable()->after('notes');
            $table->date('expected_delivery')->nullable()->after('supplier_ref');
        });

        // Add 'ordered' to the status enum
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending','ordered','received','partial','cancelled') NOT NULL DEFAULT 'received'");

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('ordered_quantity')->nullable()->after('quantity');
            $table->integer('received_quantity')->nullable()->after('ordered_quantity');
            $table->string('batch_number', 100)->nullable()->after('total');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['ordered_quantity', 'received_quantity', 'batch_number', 'expiry_date']);
        });

        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending','received','partial','cancelled') NOT NULL DEFAULT 'received'");

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['supplier_ref', 'expected_delivery']);
        });
    }
};
