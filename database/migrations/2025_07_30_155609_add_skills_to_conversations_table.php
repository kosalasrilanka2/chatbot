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
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('preferred_language')->nullable()->after('status'); // SI, TI, EN
            $table->string('preferred_domain')->nullable()->after('preferred_language'); // FINANCE, HR, IT, NETWORK
            $table->json('skill_requirements')->nullable()->after('preferred_domain'); // Store specific skill requirements
            $table->integer('language_match_score')->default(0)->after('skill_requirements'); // Matching score for assignment
            $table->integer('domain_match_score')->default(0)->after('language_match_score'); // Domain matching score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_language',
                'preferred_domain', 
                'skill_requirements',
                'language_match_score',
                'domain_match_score'
            ]);
        });
    }
};
