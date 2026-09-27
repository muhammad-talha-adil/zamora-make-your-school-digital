<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import Timeline from '@/components/public/Timeline.vue';
import ContactForm from '@/components/public/ContactForm.vue';
import Accordion from '@/components/public/Accordion.vue';

interface AdmissionStep {
    step: string;
    title: string;
    description: string;
}

interface FaqItem {
    question: string;
    answer: string;
}

const page = usePage();
const schoolName = computed(() => page.props.name as string);
const school = computed(() => page.props.school as Record<string, unknown> | null);

const heroHeadline = computed(() => (school.value?.admissions_hero_headline as string) || 'Join our school community');
const heroSubtext = computed(() => (school.value?.admissions_hero_subtext as string) || 'Admissions are open for the upcoming academic year. Submit an enquiry and our admissions team will guide you through every step.');

const admissionSteps = computed(() => {
    const steps = (school.value?.admission_steps as AdmissionStep[]) || [];

    return steps.map((step) => ({
        year: step.step,
        title: step.title,
        description: step.description,
    }));
});

const admissionsFaq = computed<FaqItem[]>(() => (school.value?.admissions_faq as FaqItem[]) || []);
</script>

<template>
    <Head :title="`Admissions - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <HeroSection
                :headline="heroHeadline"
                :subtext="heroSubtext"
                :primaryCta="{ label: 'Start Enquiry', href: '#admissions-form' }"
                :secondaryCta="{ label: 'Explore Academics', href: route('academics') }"
                illustrationSeed="admissions-welcome"
            />

            <Timeline
                v-if="admissionSteps.length"
                title="How admissions work"
                subtitle="A simple, step-by-step process from enquiry to enrollment."
                :items="admissionSteps"
            />

            <div id="admissions-form">
                <ContactForm />
            </div>

            <Accordion
                v-if="admissionsFaq.length"
                title="Admissions FAQ"
                subtitle="Answers to what parents ask us most often."
                :items="admissionsFaq"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>
