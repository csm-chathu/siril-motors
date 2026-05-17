<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])->default('bank_transfer');
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_bank_name', 100)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
