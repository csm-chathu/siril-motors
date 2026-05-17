<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // This migration previously updated a 'sold_at' column for jewelry sales. Not needed for spare parts sales.
        // No action required.
    }

    public function down(): void
    {
        // No action required.
    }
};
