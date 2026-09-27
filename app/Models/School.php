<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slogan',
        'address',
        'phone',
        'bank_name',
        'bank_account_title',
        'bank_account_no',
        'bank_branch',
        'logo_path',
        'is_active',
        'website_enabled',
        // Public website content
        'hero_headline',
        'hero_subtext',
        'hero_cta_primary_text',
        'hero_cta_primary_url',
        'hero_cta_secondary_text',
        'hero_cta_secondary_url',
        'hero_illustration_seed',
        // Trust logos
        'trust_logos',
        // About page
        'mission_statement',
        'vision_statement',
        'values',
        'leadership_team',
        'stats',
        'history_timeline',
        // Contact page
        'contact_address',
        'contact_phone',
        'contact_email',
        'contact_hours',
        'map_embed_url',
        'social_links',
        // SEO
        'meta_title',
        'meta_description',
        'og_image_path',
        // Home page
        'home_features',
        // Academics page
        'academics_hero_headline',
        'academics_hero_subtext',
        'academics_programs',
        'academics_faq',
        // Admissions page
        'admissions_hero_headline',
        'admissions_hero_subtext',
        'admission_steps',
        'admissions_faq',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'website_enabled' => 'boolean',
        'trust_logos' => 'array',
        'values' => 'array',
        'leadership_team' => 'array',
        'stats' => 'array',
        'history_timeline' => 'array',
        'social_links' => 'array',
        'home_features' => 'array',
        'academics_programs' => 'array',
        'academics_faq' => 'array',
        'admission_steps' => 'array',
        'admissions_faq' => 'array',
    ];

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
