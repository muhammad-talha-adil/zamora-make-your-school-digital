<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolWebsiteContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('school.profile.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hero_headline' => 'nullable|string|max:255',
            'hero_subtext' => 'nullable|string',
            'hero_cta_primary_text' => 'nullable|string|max:100',
            'hero_cta_primary_url' => 'nullable|string|max:255',
            'hero_cta_secondary_text' => 'nullable|string|max:100',
            'hero_cta_secondary_url' => 'nullable|string|max:255',
            'hero_illustration_seed' => 'nullable|string|max:255',

            'mission_statement' => 'nullable|string',
            'vision_statement' => 'nullable|string',

            'values' => 'nullable|array',
            'values.*.icon' => 'nullable|string|max:100',
            'values.*.title' => 'required_with:values.*|string|max:255',
            'values.*.description' => 'nullable|string',

            'leadership_team' => 'nullable|array',
            'leadership_team.*.name' => 'required_with:leadership_team.*|string|max:255',
            'leadership_team.*.role' => 'nullable|string|max:255',
            'leadership_team.*.bio' => 'nullable|string',
            'leadership_team.*.photo_url' => 'nullable|string|max:255',

            'stats' => 'nullable|array',
            'stats.*.label' => 'required_with:stats.*|string|max:255',
            'stats.*.value' => 'nullable|string|max:50',
            'stats.*.suffix' => 'nullable|string|max:20',

            'history_timeline' => 'nullable|array',
            'history_timeline.*.year' => 'required_with:history_timeline.*|string|max:20',
            'history_timeline.*.title' => 'nullable|string|max:255',
            'history_timeline.*.description' => 'nullable|string',

            'contact_address' => 'nullable|string',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'contact_hours' => 'nullable|string|max:255',
            'map_embed_url' => 'nullable|string',

            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required_with:social_links.*|string|max:100',
            'social_links.*.url' => 'nullable|string|max:255',

            'academics_hero_headline' => 'nullable|string|max:255',
            'academics_hero_subtext' => 'nullable|string',
            'academics_programs' => 'nullable|array',
            'academics_programs.*.title' => 'required_with:academics_programs.*|string|max:255',
            'academics_programs.*.description' => 'nullable|string',

            'academics_faq' => 'nullable|array',
            'academics_faq.*.question' => 'required_with:academics_faq.*|string|max:255',
            'academics_faq.*.answer' => 'nullable|string',

            'admissions_hero_headline' => 'nullable|string|max:255',
            'admissions_hero_subtext' => 'nullable|string',
            'admission_steps' => 'nullable|array',
            'admission_steps.*.title' => 'required_with:admission_steps.*|string|max:255',
            'admission_steps.*.description' => 'nullable|string',

            'admissions_faq' => 'nullable|array',
            'admissions_faq.*.question' => 'required_with:admissions_faq.*|string|max:255',
            'admissions_faq.*.answer' => 'nullable|string',

            'home_features' => 'nullable|array',
            'home_features.*.icon' => 'nullable|string|max:100',
            'home_features.*.title' => 'required_with:home_features.*|string|max:255',
            'home_features.*.description' => 'nullable|string',
            'home_features.*.seed' => 'nullable|string|max:255',
        ];
    }
}
