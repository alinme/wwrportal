<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['cui', 'director_name']);
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('cui')->nullable()->after('official_name');
            $table->string('director_name')->nullable()->after('address');
        });
    }
};
