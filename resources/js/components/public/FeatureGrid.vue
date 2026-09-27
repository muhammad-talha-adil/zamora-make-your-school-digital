<script setup lang="ts">
import { computed } from 'vue';
import {
    UserGroupIcon,
    AcademicCapIcon,
    BeakerIcon,
    TrophyIcon,
    ShieldCheckIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';

interface Feature {
    icon: string;
    title: string;
    description: string;
    seed: string;
}

const iconMap: Record<string, any> = {
    UserGroupIcon,
    AcademicCapIcon,
    BeakerIcon,
    TrophyIcon,
    ShieldCheckIcon,
    HeartIcon,
};

const defaultFeatures: Feature[] = [
    { icon: 'UserGroupIcon', title: 'Small Class Sizes', description: 'Low student-to-teacher ratios mean every child gets individual attention and is known by name.', seed: 'small-classes' },
    { icon: 'AcademicCapIcon', title: 'Experienced Faculty', description: 'Our teachers are subject specialists who bring years of classroom experience and genuine care.', seed: 'faculty' },
    { icon: 'BeakerIcon', title: 'Modern Labs & Library', description: 'Well-equipped science labs, computer labs, and a well-stocked library support hands-on learning.', seed: 'labs-library' },
    { icon: 'TrophyIcon', title: 'Sports & Co-curricular', description: 'Inter-house competitions, arts, music, and clubs help students grow beyond the classroom.', seed: 'sports' },
    { icon: 'ShieldCheckIcon', title: 'Safe Campus', description: 'A secure, well-maintained campus with attentive staff so parents can trust their child is safe.', seed: 'safe-campus' },
    { icon: 'HeartIcon', title: 'Individual Attention', description: 'We track every student\'s progress closely and support them with the care of a close-knit community.', seed: 'attention' },
];

const props = withDefaults(defineProps<{ title?: string; subtitle?: string; features?: Feature[] }>(), {
    title: 'Why families choose us',
    subtitle: 'We combine caring teachers, modern facilities, and a safe campus to help every student thrive.',
    features: () => [],
});

const features = computed(() => (props.features?.length ? props.features : defaultFeatures));
</script>

<template>
    <section class="py-16 md:py-24" aria-labelledby="features-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="features-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    {{ props.title }}
                </h2>
                <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    {{ props.subtitle }}
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <article v-for="(feature, index) in features" :key="feature.title"
                         class="group bg-card border border-border rounded-[var(--radius-lg)] shadow-sm p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 hover:border-primary/20"
                         style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                         :style="{ animationDelay: `${index * 60}ms` }">
                    <div class="relative aspect-square max-w-xs mx-auto mb-4 rounded-lg bg-gradient-to-br from-primary/5 to-primary/10 border border-border overflow-hidden">
                        <img :src="`https://picsum.photos/seed/${feature.seed}/400/400`" 
                             :alt="`${feature.title} module preview`"
                             class="absolute inset-0 w-full h-full object-cover opacity-30 group-hover:opacity-50 transition-opacity duration-300" />
                        <div class="absolute inset-0 flex items-center justify-center">
                            <component :is="iconMap[feature.icon] ?? HeartIcon" class="h-10 w-10 text-primary" />
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">{{ feature.title }}</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">{{ feature.description }}</p>
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