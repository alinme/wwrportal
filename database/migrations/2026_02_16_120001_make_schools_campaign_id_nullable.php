<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->foreignId('campaign_id')->nullable()->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->foreignId('campaign_id')->nullable(false)->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns');
        });
    }
};
