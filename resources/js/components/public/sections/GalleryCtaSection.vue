<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { useLenis } from '@/composables/useLenis';

const { target, revealed } = useScrollReveal();
const { scrollTo } = useLenis();

const scrollToAdmissions = () => scrollTo('#admissions');
</script>

<template>
    <section ref="target" class="bg-muted py-16 md:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <div class="flex flex-col items-center justify-between gap-6 rounded-2xl bg-primary p-8 text-center text-primary-foreground md:flex-row md:text-left">
                <div class="flex items-center gap-4">
                    <Icon icon="bell" class="h-8 w-8 shrink-0" />
                    <div>
                        <h3 class="text-xl font-bold">Ready to Join Us?</h3>
                        <p class="mt-1 text-sm text-primary-foreground/80">
                            Apply today to become part of our vibrant community.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-10 shrink-0 items-center justify-center rounded-full bg-background px-5 text-sm font-semibold text-primary transition-opacity hover:opacity-90"
                    @click="scrollToAdmissions"
                >
                    Apply Now
                </button>
            </div>
        </div>
    </section>
</template>
