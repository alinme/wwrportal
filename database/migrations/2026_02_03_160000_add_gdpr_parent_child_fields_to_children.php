<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->string('parent_locality')->nullable()->after('parent_full_name');
            $table->string('parent_county')->nullable()->after('parent_locality');
            $table->date('parent_birth_date')->nullable()->after('parent_county');
            $table->date('child_birth_date')->nullable()->after('parent_birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn(['parent_locality', 'parent_county', 'parent_birth_date', 'child_birth_date']);
        });
    }
};
