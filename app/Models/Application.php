<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_title',
        'company_name',
        'job_description',
        'candidate_name',
        'current_role',
        'years_experience',
        'skills_list',
        'career_highlights',
        'education_details',
        'ats_keywords',
        'resume_summary',
        'resume_experience',
        'cover_letter',
        'linkedin_post',
        'status',
        'applied_at',
        'resume_path',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsApplied()
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function getFormattedSkillsAttribute()
    {
        if (!$this->skills_list) {
            return [];
        }
        return array_map('trim', explode(',', $this->skills_list));
    }

    public function getFormattedExperienceAttribute()
    {
        if (!$this->resume_experience) {
            return [];
        }
        return array_map('trim', explode("\n", $this->resume_experience));
    }
}