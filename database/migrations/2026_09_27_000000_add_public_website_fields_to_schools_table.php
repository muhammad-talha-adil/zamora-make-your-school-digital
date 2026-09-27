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
        Schema::table('schools', function (Blueprint $table) {
            $table->text('hero_headline')->nullable()->after('website_enabled');
            $table->text('hero_subtext')->nullable()->after('hero_headline');
            $table->string('hero_cta_primary_text')->nullable()->after('hero_subtext');
            $table->string('hero_cta_primary_url')->nullable()->after('hero_cta_primary_text');
            $table->string('hero_cta_secondary_text')->nullable()->after('hero_cta_primary_url');
            $table->string('hero_cta_secondary_url')->nullable()->after('hero_cta_secondary_text');
            $table->string('hero_illustration_seed')->nullable()->after('hero_cta_secondary_url');
            $table->json('trust_logos')->nullable()->after('hero_illustration_seed');
            $table->text('mission_statement')->nullable()->after('trust_logos');
            $table->text('vision_statement')->nullable()->after('mission_statement');
            $table->json('values')->nullable()->after('vision_statement');
            $table->json('leadership_team')->nullable()->after('values');
            $table->json('stats')->nullable()->after('leadership_team');
            $table->json('history_timeline')->nullable()->after('stats');
            $table->text('contact_address')->nullable()->after('history_timeline');
            $table->string('contact_phone')->nullable()->after('contact_address');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('contact_hours')->nullable()->after('contact_email');
            $table->text('map_embed_url')->nullable()->after('contact_hours');
            $table->json('social_links')->nullable()->after('map_embed_url');
            $table->string('meta_title')->nullable()->after('social_links');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('og_image_path')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'hero_headline',
                'hero_subtext',
                'hero_cta_primary_text',
                'hero_cta_primary_url',
                'hero_cta_secondary_text',
                'hero_cta_secondary_url',
                'hero_illustration_seed',
                'trust_logos',
                'mission_statement',
                'vision_statement',
                'values',
                'leadership_team',
                'stats',
                'history_timeline',
                'contact_address',
                'contact_phone',
                'contact_email',
                'contact_hours',
                'map_embed_url',
                'social_links',
                'meta_title',
                'meta_description',
                'og_image_path',
            ]);
        });
    }
};
