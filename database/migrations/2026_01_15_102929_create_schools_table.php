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
            $table->id();
            $table->string('name');
            $table->string('slogan')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_title')->nullable();
            $table->string('bank_account_no', 64)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('website_enabled')->default(true);
            $table->text('hero_headline')->nullable();
            $table->text('hero_subtext')->nullable();
            $table->string('hero_cta_primary_text')->nullable();
            $table->string('hero_cta_primary_url')->nullable();
            $table->string('hero_cta_secondary_text')->nullable();
            $table->string('hero_cta_secondary_url')->nullable();
            $table->string('hero_illustration_seed')->nullable();
            $table->json('trust_logos')->nullable();
            $table->text('mission_statement')->nullable();
            $table->text('vision_statement')->nullable();
            $table->json('values')->nullable();
            $table->json('leadership_team')->nullable();
            $table->json('stats')->nullable();
            $table->json('history_timeline')->nullable();
            $table->text('contact_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_hours')->nullable();
            $table->text('map_embed_url')->nullable();
            $table->json('social_links')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('academics_hero_headline')->nullable();
            $table->text('academics_hero_subtext')->nullable();
            $table->json('academics_programs')->nullable();
            $table->json('academics_faq')->nullable();
            $table->json('home_features')->nullable();
            $table->string('admissions_hero_headline')->nullable();
            $table->text('admissions_hero_subtext')->nullable();
            $table->json('admission_steps')->nullable();
            $table->json('admissions_faq')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
