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
</script>

<template>
    <section v-if="campuses.length" id="campuses" ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                {{ heading }}
            </h2>

            <div
                class="mt-12 grid gap-6"
                :class="campuses.length === 1 ? 'mx-auto max-w-xl' : 'sm:grid-cols-2 lg:grid-cols-3'"
            >
                <article
                    v-for="campus in campuses"
                    :key="campus.id"
                    class="flex flex-col items-start rounded-2xl border border-border bg-card p-6 text-left shadow-sm transition-transform duration-300 hover:-translate-y-1"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                        <Icon icon="school" class="h-6 w-6 text-primary" />
                    </div>
                    <p v-if="campus.type" class="mt-4 text-xs font-semibold tracking-wide text-primary uppercase">{{ campus.type }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-foreground">{{ campus.name }}</h3>
                    <p v-if="campus.address" class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ campus.address }}</p>
                </article>
            </div>
        </div>
    </section>
</template>
