<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import InfoCards from '@/components/public/InfoCards.vue';
import ContactForm from '@/components/public/ContactForm.vue';
import MapEmbed from '@/components/public/MapEmbed.vue';
import Accordion from '@/components/public/Accordion.vue';

interface SchoolProps {
    name: string;
    slogan: string | null;
    contact_address: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    contact_hours: string | null;
    map_embed_url: string | null;
}

const page = usePage<{ school: SchoolProps | null }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

const contactFaq = [
    {
        question: 'How quickly will you respond to my enquiry?',
        answer: 'Our admissions office reviews every enquiry and typically responds within 2 business days.',
    },
    {
        question: 'Can I schedule a campus visit?',
        answer: 'Yes. Mention "Campus Visit" in your message and our team will arrange a convenient time to show you around.',
    },
    {
        question: 'What are the school office hours?',
        answer: school.value?.contact_hours ?? 'Our office is open Monday to Saturday during school hours.',
    },
    {
        question: 'I have an urgent query about my child — who do I contact?',
        answer: 'Please call the school office directly using the phone number above for anything urgent; the contact form is best for general enquiries.',
    },
];
</script>

<template>
    <Head :title="`Contact - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <!-- Hero -->
            <HeroSection
                headline="Get in touch"
                subtext="Have a question about admissions, academics, or campus life? We're here to help."
                :primaryCta="{ label: 'Apply for Admission', href: '/admissions' }"
                :secondaryCta="{ label: 'Send a Message', href: '#contact-form' }"
                illustrationSeed="school-contact"
            />

            <!-- Contact Form + Info Cards -->
            <section class="py-16 md:py-24" aria-labelledby="contact-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-3 gap-8 lg:gap-12">
                        <div class="lg:col-span-2" id="contact-form">
                            <ContactForm />
                        </div>

                        <div class="lg:col-span-1">
                            <InfoCards
                                :address="school?.contact_address"
                                :email="school?.contact_email"
                                :phone="school?.contact_phone"
                                :hours="school?.contact_hours"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Map -->
            <MapEmbed :address="school?.contact_address ?? 'Contact the school office for our campus address.'" :lat="39.7817" :lng="-89.6501" />

            <!-- FAQ -->
            <Accordion
                title="Contact FAQ"
                subtitle="Quick answers to common questions."
                :items="contactFaq"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>
