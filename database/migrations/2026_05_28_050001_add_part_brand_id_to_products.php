<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('part_brand_id')->nullable()->after('part_category_id')
                  ->constrained('part_brands')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['part_brand_id']);
            $table->dropColumn('part_brand_id');
        });
    }
};
