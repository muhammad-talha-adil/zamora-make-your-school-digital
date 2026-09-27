<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';

const page = usePage();
const schoolName = computed(() => page.props.name as string);

const sections = [
    {
        title: 'Use of this website',
        body: 'This website is provided for prospective and current parents, students, and visitors to learn about the school, submit admissions enquiries, and get in touch with our office. Content may be updated at any time without prior notice.',
    },
    {
        title: 'Admissions enquiries',
        body: 'Submitting an enquiry through this website does not guarantee admission. All applications are subject to seat availability, assessment, and document verification as described on our Admissions page.',
    },
    {
        title: 'Accuracy of information',
        body: 'We make every effort to keep information on this website accurate and up to date, including fees, academic programs, and events. For the most current details, please contact the school office directly.',
    },
    {
        title: 'Intellectual property',
        body: 'The school\'s name, logo, and content on this website may not be reproduced or used without written permission from the school administration.',
    },
    {
        title: 'External links',
        body: 'This website may link to third-party services (such as maps or social media). We are not responsible for the content or privacy practices of those external sites.',
    },
    {
        title: 'Contact us',
        body: 'Questions about these terms can be directed to the school office through our Contact page.',
    },
];
</script>

<template>
    <Head :title="`Terms of Service - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1 py-16 md:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <header class="mb-12">
                    <h1 class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                        Terms of Service
                    </h1>
                    <p class="text-base text-muted-foreground leading-relaxed">
                        Last updated: September 2026. These terms govern your use of the {{ schoolName }} website.
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
