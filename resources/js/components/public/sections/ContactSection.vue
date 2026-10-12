<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import ContactForm from '@/components/public/ContactForm.vue';
import MapEmbed from '@/components/public/MapEmbed.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

defineProps<{
    address: string | null;
    phone: string | null;
    email: string | null;
    hours: string | null;
    mapEmbedUrl: string | null;
}>();

const { target, revealed } = useScrollReveal();
</script>

<template>
    <section id="contact" ref="target" class="bg-muted/30 py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div :class="revealed ? 'reveal-visible' : 'reveal'">
                <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">Visit or get in touch</h2>
                <p class="mt-4 max-w-md text-base leading-relaxed text-muted-foreground">
                    Questions about admissions, academics, or anything else? Reach out and our team will respond promptly.
                </p>

                <ul class="mt-8 space-y-4">
                    <li v-if="address" class="flex gap-3 text-sm text-foreground">
                        <Icon icon="map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ address }}
                    </li>
                    <li v-if="phone" class="flex gap-3 text-sm text-foreground">
                        <Icon icon="phone" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ phone }}
                    </li>
                    <li v-if="email" class="flex gap-3 text-sm text-foreground">
                        <Icon icon="mail" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ email }}
                    </li>
                    <li v-if="hours" class="flex gap-3 text-sm text-foreground">
                        <Icon icon="clock" class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                        {{ hours }}
                    </li>
                </ul>

                <div class="mt-8">
                    <MapEmbed :embed-url="mapEmbedUrl" />
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-6 md:p-8" :class="revealed ? 'reveal-visible' : 'reveal'">
                <ContactForm />
            </div>
        </div>
    </section>
</template>
