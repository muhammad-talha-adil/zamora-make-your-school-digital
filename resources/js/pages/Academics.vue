<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import SplitFeature from '@/components/public/SplitFeature.vue';
import Accordion from '@/components/public/Accordion.vue';

interface AcademicsProgram {
    title: string;
    description: string;
    features: string[];
    image_seed: string;
}

interface FaqItem {
    question: string;
    answer: string;
}

const page = usePage();
const schoolName = computed(() => page.props.name as string);
const school = computed(() => page.props.school as Record<string, unknown> | null);

const heroHeadline = computed(() => (school.value?.academics_hero_headline as string) || 'A curriculum built for every stage of growth');
const heroSubtext = computed(() => (school.value?.academics_hero_subtext as string) || 'From Early Years to Secondary, our academic program balances strong fundamentals with the skills students need for what comes next.');
const programs = computed<AcademicsProgram[]>(() => (school.value?.academics_programs as AcademicsProgram[]) || []);
const academicsFaq = computed<FaqItem[]>(() => (school.value?.academics_faq as FaqItem[]) || []);
</script>

<template>
    <Head :title="`Academics - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <HeroSection
                :headline="heroHeadline"
                :subtext="heroSubtext"
                :primaryCta="{ label: 'Apply Now', href: route('admissions') }"
                :secondaryCta="{ label: 'Contact Us', href: route('contact') }"
                illustrationSeed="academics-overview"
            />

            <SplitFeature
                v-for="(program, index) in programs"
                :key="program.title"
                :title="program.title"
                :description="program.description"
                :features="program.features"
                :cta="index === 0 ? { label: 'How to Apply', href: route('admissions') } : { label: 'Meet Our Faculty', href: route('about') }"
                :imageSeed="program.image_seed"
                :reverse="index % 2 === 1"
            />

            <Accordion
                v-if="academicsFaq.length"
                title="Academics FAQ"
                subtitle="Common questions from parents about our academic program."
                :items="academicsFaq"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>
