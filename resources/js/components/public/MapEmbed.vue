<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface MapEmbedProps {
    address?: string;
    lat?: number;
    lng?: number;
    zoom?: number;
}

const props = withDefaults(defineProps<MapEmbedProps>(), {
    address: 'Contact the school office for our campus address.',
    lat: 39.7817,
    lng: -89.6501,
    zoom: 15,
});

const page = usePage();
const schoolName = computed(() => (page.props.school as { name?: string } | null)?.name ?? (page.props.name as string));
</script>

<template>
    <section class="py-16 md:py-24" aria-labelledby="map-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header class="text-center max-w-3xl mx-auto mb-12">
                <h2 id="map-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    Visit our campus
                </h2>
                <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    We'd love to show you around. Schedule a visit to see {{ schoolName }} in person.
                </p>
            </header>

            <div class="rounded-[var(--radius-xl)] overflow-hidden border border-border bg-muted">
<iframe
                        :src="`https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3082.123456789!2d${props.lng}!3d${props.lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMznCsDQ3JzE0LjEiTiA4OcKwMzknMDAuNCJX!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus`"
                        width="100%"
                        height="450"
                        style="border:0;"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        :title="`${schoolName} Campus Location`"
                        class="w-full h-[450px] md:h-[500px]"
                    ></iframe>
                <div class="p-6 bg-background border-t border-border">
                    <address class="not-italic text-muted-foreground">
                        <p class="font-medium text-foreground mb-1">{{ schoolName }}</p>
                        <p>{{ props.address }}</p>
                    </address>
                </div>
            </div>
        </div>
    </section>
</template>