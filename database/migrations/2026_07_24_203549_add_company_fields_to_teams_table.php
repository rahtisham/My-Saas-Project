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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('is_personal');
            $table->string('website')->nullable()->after('industry');
            $table->string('logo_path')->nullable()->after('website');
            $table->string('timezone')->default('UTC')->after('logo_path');
            $table->string('country')->nullable()->after('timezone');
            $table->string('plan')->default('free')->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['industry', 'website', 'logo_path', 'timezone', 'country', 'plan']);
        });
    }
};
