<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import FeatureGrid from '@/components/public/FeatureGrid.vue';
import SplitFeature from '@/components/public/SplitFeature.vue';
import TestimonialCard from '@/components/public/TestimonialCard.vue';
import StatCounter from '@/components/public/StatCounter.vue';
import Accordion from '@/components/public/Accordion.vue';

interface SchoolProps {
    id: number;
    name: string;
    logo_path: string | null;
    slogan: string | null;
    hero_headline: string | null;
    hero_subtext: string | null;
    hero_cta_primary_text: string | null;
    hero_cta_primary_url: string | null;
    hero_cta_secondary_text: string | null;
    hero_cta_secondary_url: string | null;
    hero_illustration_seed: string | null;
    stats?: Array<{ label: string; value: number; suffix?: string }>;
    home_features?: Array<{ icon: string; title: string; description: string; seed: string }>;
}

interface LiveStats {
    students: number;
    teachers: number;
}

const page = usePage<{ school: SchoolProps | null; live_stats: LiveStats }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

/** Students/Teachers rows always use the live DB counts, not the admin-entered numbers. */
const stats = computed(() => {
    const liveByLabel: Record<string, number> = {
        students: page.props.live_stats?.students ?? 0,
        teachers: page.props.live_stats?.teachers ?? 0,
    };

    return (school.value?.stats ?? []).map((stat) => {
        const live = liveByLabel[stat.label.toLowerCase()];

        return live !== undefined ? { ...stat, value: live } : stat;
    });
});

const faqItems = [
    {
        question: 'How do I apply for admission?',
        answer: 'Visit our Admissions page and submit an enquiry form. Our admissions team will contact you within 2 business days to guide you through the process.',
    },
    {
        question: 'What grade levels does the school offer?',
        answer: 'We offer classes from Early Years through Secondary. See the Academics page for details on each stage.',
    },
    {
        question: 'What are the school timings?',
        answer: 'Classes run Monday to Saturday, morning to early afternoon. Exact timings vary slightly by grade level — contact the office for your child\'s section.',
    },
    {
        question: 'Do you offer transport for students?',
        answer: 'Yes, school transport is available on select routes. Ask our admissions team about routes near your home.',
    },
    {
        question: 'Can I visit the campus before applying?',
        answer: 'Absolutely. Campus visits are part of our admissions process and can also be arranged separately — just reach out via the Contact page.',
    },
];
</script>

<template>
    <Head :title="schoolName" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <!-- Hero -->
            <HeroSection
                :headline="school?.hero_headline ?? `Welcome to ${schoolName}`"
                :subtext="school?.hero_subtext ?? 'A caring learning community where every student is known, challenged, and supported.'"
                :primaryCta="{ label: school?.hero_cta_primary_text ?? 'Apply for Admission', href: school?.hero_cta_primary_url ?? route('admissions') }"
                :secondaryCta="{ label: school?.hero_cta_secondary_text ?? 'Explore Academics', href: school?.hero_cta_secondary_url ?? route('academics') }"
                :illustrationSeed="school?.hero_illustration_seed ?? 'school-campus'"
            />

            <!-- Stats -->
            <StatCounter v-if="stats.length" :stats="stats" />

            <!-- Why families choose us -->
            <FeatureGrid v-if="school?.home_features?.length" :features="school.home_features" />
            <FeatureGrid v-else />

            <!-- Academics highlight -->
            <SplitFeature
                title="A curriculum built for every stage of growth"
                description="From Early Years to Secondary, our teachers follow a structured curriculum with regular assessments, so every student gets the support and challenge they need."
                :features="['National curriculum alignment', 'Small class sizes', 'Subject-specialist teachers', 'Term-end report cards', 'Remedial support where needed']"
                :cta="{ label: 'See Academics', href: route('academics') }"
                imageSeed="academics-classroom"
            />

            <!-- Campus life -->
            <SplitFeature
                title="More than textbooks"
                description="Sports, arts, debate, and community service run alongside academics, helping students build confidence, teamwork, and interests beyond the classroom."
                :features="['Inter-house sports competitions', 'Art & music programs', 'Debate and public speaking clubs', 'Science and robotics club', 'Annual community service drive']"
                :cta="{ label: 'Learn About Admissions', href: route('admissions') }"
                imageSeed="academics-activities"
                reverse
            />

            <!-- Testimonials -->
            <TestimonialCard />

            <!-- FAQ -->
            <Accordion
                title="Frequently asked questions"
                subtitle="Everything parents ask us before enrolling."
                :items="faqItems"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>
