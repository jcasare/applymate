<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Job details
            $table->string('job_title');
            $table->string('company_name');
            $table->text('job_description');
            
            // Candidate profile
            $table->string('candidate_name');
            $table->string('current_role')->nullable();
            $table->integer('years_experience')->default(0);
            $table->text('skills_list')->nullable();
            $table->text('career_highlights')->nullable();
            $table->text('education_details')->nullable();
            
            // Generated content
            $table->text('ats_keywords')->nullable();
            $table->text('resume_summary')->nullable();
            $table->text('resume_experience')->nullable();
            $table->text('cover_letter')->nullable();
            $table->text('linkedin_post')->nullable();
            
            // Meta
            $table->string('status')->default('generated'); // generated, saved, applied
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};