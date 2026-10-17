<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { ref } from 'vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface StaffMember {
    id: number;
    name: string;
    designation: string | null;
    department: string | null;
    photo_url: string | null;
}

defineProps<{ staff: StaffMember[] }>();

const { target, revealed } = useScrollReveal();

const track = ref<HTMLElement | null>(null);
const scrollByAmount = (dir: 1 | -1) => {
    track.value?.scrollBy({ left: dir * 320, behavior: 'smooth' });
};
</script>

<template>
    <section id="staff" ref="target" class="bg-muted py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                    Our Staff
                </h2>
                <div class="hidden gap-2 sm:flex">
                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card text-foreground transition-colors hover:bg-accent" @click="scrollByAmount(-1)">
                        <Icon icon="chevron-left" class="h-4 w-4" />
                    </button>
                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card text-foreground transition-colors hover:bg-accent" @click="scrollByAmount(1)">
                        <Icon icon="chevron-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div ref="track" class="mt-10 flex gap-5 overflow-x-auto pb-4 [scrollbar-width:none] [-ms-overflow-style:none] snap-x snap-mandatory [&::-webkit-scrollbar]:hidden">
                <article
                    v-for="member in staff"
                    :key="member.id"
                    class="w-56 shrink-0 snap-start overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
                >
                    <div class="aspect-4/5 w-full overflow-hidden bg-muted">
                        <img
                            v-if="member.photo_url"
                            :src="member.photo_url"
                            :alt="member.name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-3xl font-bold text-muted-foreground">
                            {{ member.name.charAt(0) }}
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="truncate text-sm font-semibold text-foreground">{{ member.name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ member.designation ?? member.department ?? 'Staff' }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
