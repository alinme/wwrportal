<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedInteger('kits_returned')->default(0)->after('target_kits');
            $table->unsignedInteger('kits_received_from_return')->default(0)->after('kits_returned');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['kits_returned', 'kits_received_from_return']);
        });
    }
};
