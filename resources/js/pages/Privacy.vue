<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';

const page = usePage();
const schoolName = computed(() => page.props.name as string);

const sections = [
    {
        title: 'Information we collect',
        body: 'We collect information you provide directly, such as a student or parent\'s name, contact details, and enquiry messages submitted through our admissions and contact forms. We do not collect information beyond what is needed to respond to your enquiry or process an admission.',
    },
    {
        title: 'How we use your information',
        body: 'Information submitted through this website is used only to respond to enquiries, process admissions, and share relevant updates about the school. We do not sell or rent your data to third parties.',
    },
    {
        title: 'Student data protection',
        body: 'Any student records maintained by the school — attendance, academic performance, fee records — are stored securely and accessible only to authorized school staff. Parents and guardians may request a copy of their child\'s records at any time.',
    },
    {
        title: 'Cookies',
        body: 'This website uses only essential cookies required for the site to function correctly, such as remembering your appearance (light/dark mode) preference. We do not use third-party advertising or tracking cookies.',
    },
    {
        title: 'Your rights',
        body: 'You may request access to, correction of, or deletion of your personal information at any time by contacting the school office directly.',
    },
    {
        title: 'Contact us',
        body: 'For any questions about this privacy policy or how your data is handled, please reach out through our Contact page.',
    },
];
</script>

<template>
    <Head :title="`Privacy Policy - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1 py-16 md:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <header class="mb-12">
                    <h1 class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                        Privacy Policy
                    </h1>
                    <p class="text-base text-muted-foreground leading-relaxed">
                        Last updated: September 2026. This policy explains how {{ schoolName }} collects, uses, and protects
                        information shared through this website and our school records.
                    </p>
                </header>

                <div class="space-y-10">
                    <section v-for="section in sections" :key="section.title">
                        <h2 class="text-xl font-semibold text-foreground mb-3">{{ section.title }}</h2>
                        <p class="text-base text-muted-foreground leading-relaxed">{{ section.body }}</p>
                    </section>
                </div>
            </div>
        </main>

        <PortfolioFooter />
    </div>
</template>
