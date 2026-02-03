<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('address');
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->unsignedInteger('target_kits')->default(0)->after('access_token');
        });

        Schema::table('structures', function (Blueprint $table) {
            $table->unsignedInteger('target_kits')->default(0)->after('address');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('educator_name');
            $table->unsignedInteger('target_kits')->default(0)->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'contact_phone', 'target_kits']);
        });
        Schema::table('structures', function (Blueprint $table) {
            $table->dropColumn('target_kits');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'target_kits']);
        });
    }
};
