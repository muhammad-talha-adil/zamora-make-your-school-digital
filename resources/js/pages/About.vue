<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import StatCounter from '@/components/public/StatCounter.vue';
import Timeline from '@/components/public/Timeline.vue';
import Accordion from '@/components/public/Accordion.vue';

interface SchoolProps {
    name: string;
    slogan: string | null;
    mission_statement: string | null;
    vision_statement: string | null;
    values?: Array<{ icon: string; title: string; description: string }>;
    leadership_team?: Array<{ name: string; role: string; bio: string; photo_url: string }>;
    stats?: Array<{ label: string; value: number; suffix?: string }>;
    history_timeline?: Array<{ year: string; title: string; description: string }>;
}

interface LiveStats {
    students: number;
    teachers: number;
}

const page = usePage<{ school: SchoolProps | null; live_stats: LiveStats }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

const values = computed(() => school.value?.values ?? []);
const leadership = computed(() => (school.value?.leadership_team ?? []).map((l, i) => ({ ...l, seed: `leader-${i}` })));

/**
 * Students/Teachers are always the live counts from `live_stats`, not the
 * admin-entered numbers on the matching `school.stats` rows (those would go
 * stale); any other admin-entered rows (years, classrooms, ...) pass through.
 */
const stats = computed(() => {
    const liveByLabel: Record<string, number> = {
        students: page.props.live_stats?.students ?? 0,
        teachers: page.props.live_stats?.teachers ?? 0,
    };

    return (school.value?.stats ?? []).map((stat) => {
        const live = liveByLabel[stat.label.toLowerCase()];

        return live !== undefined ? { ...stat, value: live } : stat;
    });
});
const timeline = computed(() => school.value?.history_timeline ?? []);

const aboutFaq = [
    {
        question: 'What is the school\'s teaching philosophy?',
        answer: 'We believe strong fundamentals, small class sizes, and personal attention build both academic ability and character. Every teacher is trained to support each student\'s individual pace.',
    },
    {
        question: 'How can I meet the leadership team?',
        answer: 'Parents are welcome to schedule a meeting with the Principal or Vice Principal through the school office — just call ahead or use the Contact page.',
    },
    {
        question: 'Does the school offer scholarships?',
        answer: 'Yes, merit-based and need-based fee concessions are available. Ask our admissions team for details when you submit your enquiry.',
    },
    {
        question: 'Is the school affiliated with any board?',
        answer: 'Yes, we follow the national curriculum framework and are registered with the relevant education board. Certificates and report cards are recognised for transfers.',
    },
];
</script>

<template>
    <Head :title="`About - ${schoolName}`" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <!-- Hero -->
            <HeroSection
                headline="Our story"
                :subtext="school?.slogan ?? 'Built by educators, for our community — every decision here starts with what is best for the student.'"
                :primaryCta="{ label: 'Apply for Admission', href: '/admissions' }"
                :secondaryCta="{ label: 'Contact Us', href: '/contact' }"
                illustrationSeed="school-leadership"
            />

            <!-- Mission/Vision/Values -->
            <section v-if="school?.mission_statement || values.length" class="py-16 md:py-24" aria-labelledby="values-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header class="text-center max-w-3xl mx-auto mb-16">
                        <h2 id="values-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                            Our mission &amp; values
                        </h2>
                        <p v-if="school?.mission_statement" class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                            {{ school.mission_statement }}
                        </p>
                    </header>

                    <div v-if="values.length" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <article v-for="(value, index) in values" :key="value.title"
                                 class="bg-card border border-border rounded-[var(--radius-lg)] p-6 md:p-8 text-center"
                                 style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                                 :style="{ animationDelay: `${index * 60}ms` }">
                            <div class="w-14 h-14 mx-auto mb-4 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-foreground mb-2">{{ value.title }}</h3>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ value.description }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Leadership -->
            <section v-if="leadership.length" class="py-16 md:py-24 bg-muted/30" aria-labelledby="leadership-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header class="text-center max-w-3xl mx-auto mb-16">
                        <h2 id="leadership-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                            School leadership
                        </h2>
                        <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                            The team guiding our students, teachers, and campus every day.
                        </p>
                    </header>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <article v-for="(leader, index) in leadership" :key="leader.name"
                                 class="bg-card border border-border rounded-[var(--radius-lg)] overflow-hidden"
                                 style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                                 :style="{ animationDelay: `${index * 60}ms` }">
                            <div class="aspect-square relative overflow-hidden">
                                <img :src="leader.photo_url ?? `https://picsum.photos/seed/${leader.seed}/400/400`"
                                     :alt="`${leader.name} photo`"
                                     class="absolute inset-0 w-full h-full object-cover" />
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-foreground mb-1">{{ leader.name }}</h3>
                                <p class="text-sm text-primary mb-4">{{ leader.role }}</p>
                                <p class="text-sm text-muted-foreground leading-relaxed">{{ leader.bio }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Stats -->
            <StatCounter v-if="stats.length" :stats="stats" />

            <!-- Timeline -->
            <Timeline
                v-if="timeline.length"
                title="Our journey"
                subtitle="From our founding to today."
                :items="timeline"
            />

            <!-- FAQ -->
            <Accordion
                title="About the school"
                subtitle="Common questions from parents."
                :items="aboutFaq"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
