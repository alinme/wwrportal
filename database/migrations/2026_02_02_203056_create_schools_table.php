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
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('campaign_id')->constrained();
            $table->string('official_name'); // e.g., "Scoala Gimnaziala Nr 1"
            $table->string('cui'); // Tax ID
            $table->string('address'); // Google Maps formatted address
            $table->string('director_name');
            $table->string('access_token')->unique(); // unique token for Magic Link login
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
