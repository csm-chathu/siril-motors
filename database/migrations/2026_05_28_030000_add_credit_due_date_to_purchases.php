<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('credit_due_date')->nullable()->after('expected_delivery');
        });
    }

    public function down(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('credit_due_date');
        });
    }
};
