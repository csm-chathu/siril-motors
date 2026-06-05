<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('credit_settled_at')->nullable()->after('credit_due_date');
            $table->foreignId('credit_settlement_journal_id')->nullable()->constrained('journal_entries')->nullOnDelete()->after('credit_settled_at');
        });
    }

    public function down(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['credit_settlement_journal_id']);
            $table->dropColumn(['credit_settled_at', 'credit_settlement_journal_id']);
        });
    }
};
