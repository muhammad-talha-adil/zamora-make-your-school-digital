<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target, revealed } = useScrollReveal();

const scrollToAdmissions = () => {
    document.querySelector('#admissions')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const gallerySeeds = ['school-life-1', 'school-life-2', 'school-life-3', 'school-life-4', 'school-life-5', 'school-life-6'];
</script>

<template>
    <section ref="target" class="bg-muted/30 py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                    Life at the Academy
                </h2>
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div
                        v-for="(seed, index) in gallerySeeds"
                        :key="seed"
                        class="aspect-square overflow-hidden rounded-xl"
                        :class="index === 4 ? 'ring-2 ring-primary ring-offset-2 ring-offset-background' : ''"
                    >
                        <img
                            :src="`https://picsum.photos/seed/${seed}/400/400`"
                            alt="Life on campus"
                            class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-center rounded-2xl bg-primary p-8 text-primary-foreground">
                <Icon icon="bell" class="h-8 w-8" />
                <h3 class="mt-4 text-xl font-bold">Ready to Join Us?</h3>
                <p class="mt-2 text-sm leading-relaxed text-primary-foreground/80">
                    Apply today to become part of our vibrant community.
                </p>
                <button
                    type="button"
                    class="mt-6 inline-flex h-10 w-fit items-center justify-center rounded-full bg-accent px-5 text-sm font-semibold text-accent-foreground transition-opacity hover:opacity-90"
                    @click="scrollToAdmissions"
                >
                    Apply Now
                </button>
            </div>
        </div>
    </section>
</template>
