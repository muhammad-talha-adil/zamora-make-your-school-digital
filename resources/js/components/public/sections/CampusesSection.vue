<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface PublicCampus {
    id: number;
    name: string;
    address: string | null;
    type: string | null;
}

const props = defineProps<{ campuses: PublicCampus[] }>();

const { target, revealed } = useScrollReveal();

const heading = computed(() => (props.campuses.length > 1 ? 'Our Campuses' : 'Our School'));

/**
 * Verified, free-license Unsplash building photos (no campus photo field
 * exists in the database), cycled by index rather than a random seed so the
 * same campus always gets the same photo and multiple campuses don't repeat
 * the one already used in the About section.
 */
const BUILDING_PHOTOS = [
    'photo-1591123120675-6f7f1aae0e5b',
    'photo-1787094486063-81b52e9ba62b',
    'photo-1741528804373-2bedeb308ce1',
];

const buildingPhoto = (index: number) => `https://images.unsplash.com/${BUILDING_PHOTOS[index % BUILDING_PHOTOS.length]}?auto=format&fit=crop&w=900&q=75`;
</script>

<template>
    <section v-if="campuses.length" id="campuses" ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                {{ heading }}
            </h2>

            <div
                class="mt-12 grid gap-8"
                :class="campuses.length === 1 ? 'mx-auto max-w-2xl' : 'sm:grid-cols-2 lg:grid-cols-3'"
            >
                <article
                    v-for="(campus, index) in campuses"
                    :key="campus.id"
                    class="group overflow-hidden rounded-2xl border border-border bg-card text-left shadow-sm transition-transform duration-300 hover:-translate-y-1"
                >
                    <div class="aspect-16/10 w-full overflow-hidden">
                        <img
                            :src="buildingPhoto(index)"
                            :alt="`${campus.name} building`"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                    <div class="p-6">
                        <p v-if="campus.type" class="text-xs font-semibold tracking-wide text-primary uppercase">{{ campus.type }} Campus</p>
                        <h3 class="mt-1 text-xl font-semibold text-foreground">{{ campus.name }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                            A full-facility campus serving students from Early Years through Secondary, with
                            qualified teachers and modern classrooms.
                        </p>
                        <p v-if="campus.address" class="mt-4 flex items-center gap-2 text-sm text-foreground">
                            <Icon icon="map-pin" class="h-4 w-4 shrink-0 text-primary" />
                            {{ campus.address }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
