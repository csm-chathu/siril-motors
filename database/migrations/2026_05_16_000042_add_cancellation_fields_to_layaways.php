<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layaways', function (Blueprint $table) {
            if (!Schema::hasColumn('layaways', 'cancelled_at'))
                $table->timestamp('cancelled_at')->nullable()->after('collected_at');
            if (!Schema::hasColumn('layaways', 'cancellation_reason'))
                $table->string('cancellation_reason', 500)->nullable()->after('cancelled_at');
            if (!Schema::hasColumn('layaways', 'refund_type'))
                $table->string('refund_type', 20)->nullable()->after('cancellation_reason');
            if (!Schema::hasColumn('layaways', 'cancellation_fee'))
                $table->decimal('cancellation_fee', 12, 2)->default(0)->after('refund_type');
            if (!Schema::hasColumn('layaways', 'refund_amount'))
                $table->decimal('refund_amount', 12, 2)->default(0)->after('cancellation_fee');
            if (!Schema::hasColumn('layaways', 'refund_method'))
                $table->string('refund_method', 30)->nullable()->after('refund_amount');
            if (!Schema::hasColumn('layaways', 'cancellation_journal_id'))
                $table->unsignedBigInteger('cancellation_journal_id')->nullable()->after('refund_method');
        });

        // Seed Cancellation Fee Income account if not present
        DB::table('accounts')->insertOrIgnore([
            'code'       => '4050',
            'name'       => 'Cancellation Fee Income',
            'type'       => 'income',
            'is_system'  => true,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('layaways', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at', 'cancellation_reason', 'refund_type',
                'cancellation_fee', 'refund_amount', 'refund_method', 'cancellation_journal_id',
            ]);
        });

        DB::table('accounts')->where('code', '4050')->where('is_system', true)->delete();
    }
};
