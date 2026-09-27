<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('academics_hero_headline')->nullable()->after('og_image_path');
            $table->text('academics_hero_subtext')->nullable()->after('academics_hero_headline');
            $table->json('academics_programs')->nullable()->after('academics_hero_subtext');
            $table->json('academics_faq')->nullable()->after('academics_programs');

            $table->string('admissions_hero_headline')->nullable()->after('academics_faq');
            $table->text('admissions_hero_subtext')->nullable()->after('admissions_hero_headline');
            $table->json('admission_steps')->nullable()->after('admissions_hero_subtext');
            $table->json('admissions_faq')->nullable()->after('admission_steps');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn([
                'academics_hero_headline',
                'academics_hero_subtext',
                'academics_programs',
                'academics_faq',
                'admissions_hero_headline',
                'admissions_hero_subtext',
                'admission_steps',
                'admissions_faq',
            ]);
        });
    }
};
