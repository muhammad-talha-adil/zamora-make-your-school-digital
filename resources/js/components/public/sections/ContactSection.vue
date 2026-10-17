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
    <section id="contact" ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                Visit or get in touch
            </h2>
            <p class="mt-4 max-w-md text-base leading-relaxed text-muted-foreground" :class="revealed ? 'reveal-visible' : 'reveal'">
                Questions about admissions, academics, or anything else? Reach out and our team will respond promptly.
            </p>

            <div class="mt-10 grid gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="relative overflow-hidden rounded-2xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                    <img
                        src="https://images.unsplash.com/photo-1543325042-c67825847491?auto=format&fit=crop&w=900&q=75"
                        alt="School reception"
                        class="aspect-square w-full object-cover"
                        loading="lazy"
                    />
                    <div class="absolute inset-x-4 bottom-4 rounded-2xl border border-white/20 bg-card/80 p-5 shadow-lg backdrop-blur-md">
                        <ul class="space-y-3">
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
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-sm md:p-8" :class="revealed ? 'reveal-visible' : 'reveal'">
                        <ContactForm />
                    </div>
                    <MapEmbed :embed-url="mapEmbedUrl" />
                </div>
            </div>
        </div>
    </section>
</template>
