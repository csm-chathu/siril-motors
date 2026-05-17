<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_type_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('part_category_id');
            $table->unsignedBigInteger('quality_type_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
            $table->foreign('part_category_id')->references('id')->on('part_categories')->onDelete('cascade');
            $table->foreign('quality_type_id')->references('id')->on('quality_types')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    public function down() {
        Schema::dropIfExists('products');
    }
};
