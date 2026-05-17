<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->string('origin_country', 100)->nullable()->after('description');
        });

        Schema::table('part_categories', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table('quality_types', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table('models', function (Blueprint $table) {
            $table->unsignedSmallInteger('year_from')->nullable()->after('name');
            $table->unsignedSmallInteger('year_to')->nullable()->after('year_from');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', fn($t) => $t->dropColumn('description'));
        Schema::table('brands', fn($t) => $t->dropColumn(['description', 'origin_country']));
        Schema::table('part_categories', fn($t) => $t->dropColumn('description'));
        Schema::table('quality_types', fn($t) => $t->dropColumn('description'));
        Schema::table('models', fn($t) => $t->dropColumn(['year_from', 'year_to']));
    }
};
