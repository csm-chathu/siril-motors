<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add all missing columns to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('customer_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('user_id');
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
            $table->decimal('tax_rate', 8, 4)->default(0)->after('tax');
            $table->decimal('total', 12, 2)->default(0)->after('tax_rate');
            $table->decimal('maintenance_amount', 12, 2)->default(0)->after('tax');
            $table->enum('payment_method', ['cash','card','bank_transfer','cheque','other'])->default('cash')->after('total');
            $table->enum('payment_status', ['pending','paid','partial','refunded'])->default('paid')->after('payment_method');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
            $table->text('notes')->nullable()->after('amount_paid');
            $table->boolean('is_draft')->default(false)->after('notes');
            $table->timestamp('sold_at')->nullable()->after('is_draft');
            $table->softDeletes();
        });

        // sale_items: add discount column (total_price → total already exists as total_price, add discount)
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
            $table->decimal('total', 12, 2)->default(0)->after('discount');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('vehicle_number', 30)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'total']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number', 'user_id', 'subtotal', 'discount', 'tax', 'tax_rate',
                'total', 'maintenance_amount', 'payment_method', 'payment_status',
                'amount_paid', 'notes', 'is_draft', 'sold_at', 'deleted_at',
            ]);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('vehicle_number');
        });
    }
};
