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
            $table->timestamp('cheque_settled_at')->nullable()->after('cheque_bank_name');
            $table->unsignedBigInteger('settlement_journal_id')->nullable()->after('cheque_settled_at');
        });

        // Seed "Cheques Payable" account if not already present
        if (!DB::table('accounts')->where('code', '2050')->exists()) {
            DB::table('accounts')->insert([
                'code'        => '2050',
                'name'        => 'Cheques Payable',
                'type'        => 'liability',
                'description' => 'Post-dated cheques issued to suppliers, pending bank clearance',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['cheque_settled_at', 'settlement_journal_id']);
        });
        DB::table('accounts')->where('code', '2050')->delete();
    }
};
