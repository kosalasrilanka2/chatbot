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
        Schema::create('agent_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->enum('skill_type', ['language', 'domain']);
            $table->string('skill_code'); // SI, TI, EN for languages; FINANCE, HR, IT, NETWORK for domains
            $table->string('skill_name'); // Sinhala, Tamil, English, Finance, HR, IT, Network
            $table->integer('proficiency_level')->default(1); // 1-5 scale for language/domain expertise
            $table->timestamps();
            
            // Ensure unique skill per agent
            $table->unique(['agent_id', 'skill_type', 'skill_code']);
            
            // Index for faster queries
            $table->index(['skill_type', 'skill_code']);
            $table->index(['agent_id', 'skill_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_skills');
    }
};
