<script setup lang="ts">
import { computed } from 'vue';
import {
    MapPinIcon,
    MailIcon,
    PhoneIcon,
    ClockIcon
} from 'lucide-vue-next';

interface InfoCard {
    icon: any;
    title: string;
    description: string;
    link?: string;
    linkText?: string;
}

const props = withDefaults(defineProps<{
    address?: string | null;
    email?: string | null;
    phone?: string | null;
    hours?: string | null;
}>(), {
    address: null,
    email: null,
    phone: null,
    hours: null,
});

const infoCards = computed<InfoCard[]>(() => [
    {
        icon: MapPinIcon,
        title: 'Visit Us',
        description: props.address ?? 'Contact the school office for our campus address.',
        link: props.address ? `https://maps.google.com/?q=${encodeURIComponent(props.address)}` : undefined,
        linkText: 'Get Directions',
    },
    {
        icon: MailIcon,
        title: 'Email Us',
        description: props.email ?? 'Contact the school office for our email address.',
        link: props.email ? `mailto:${props.email}` : undefined,
        linkText: 'Send Email',
    },
    {
        icon: PhoneIcon,
        title: 'Call Us',
        description: props.phone ?? 'Contact the school office for our phone number.',
        link: props.phone ? `tel:${props.phone.replace(/[^+\d]/g, '')}` : undefined,
        linkText: 'Call Now',
    },
    {
        icon: ClockIcon,
        title: 'Business Hours',
        description: props.hours ?? 'Contact the school office for our current hours.',
    },
]);
</script>

<template>
    <section class="py-16 md:py-24 bg-muted/30" aria-labelledby="info-cards-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="info-cards-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    Get in touch
                </h2>
                <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    Choose the most convenient way to reach our team.
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <article v-for="(card, index) in infoCards" :key="card.title"
                         class="bg-card border border-border rounded-[var(--radius-lg)] shadow-sm p-6 transition-shadow hover:shadow-md"
                         style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                         :style="{ animationDelay: `${index * 60}ms` }">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 text-primary">
                        <component :is="card.icon" class="w-6 h-6" aria-hidden="true" />
                    </div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">{{ card.title }}</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed mb-4 whitespace-pre-line">{{ card.description }}</p>
                    <a v-if="card.link" :href="card.link" :target="card.link.startsWith('http') ? '_blank' : undefined" :rel="card.link.startsWith('http') ? 'noopener noreferrer' : undefined"
                       class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                        {{ card.linkText }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </article>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>