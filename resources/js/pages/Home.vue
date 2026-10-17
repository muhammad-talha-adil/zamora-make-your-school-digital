<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/sections/HeroSection.vue';
import StatsBandSection from '@/components/public/sections/StatsBandSection.vue';
import AboutSection from '@/components/public/sections/AboutSection.vue';
import ProgramsSection from '@/components/public/sections/ProgramsSection.vue';
import CampusLifeSection from '@/components/public/sections/CampusLifeSection.vue';
import TestimonialsSection from '@/components/public/sections/TestimonialsSection.vue';
import GalleryCtaSection from '@/components/public/sections/GalleryCtaSection.vue';
import AdmissionsSection from '@/components/public/sections/AdmissionsSection.vue';
import FaqSection from '@/components/public/sections/FaqSection.vue';
import ContactSection from '@/components/public/sections/ContactSection.vue';

interface SchoolProps {
    id: number;
    name: string;
    hero_headline: string | null;
    hero_subtext: string | null;
    hero_cta_primary_text: string | null;
    hero_cta_primary_url: string | null;
    hero_cta_secondary_text: string | null;
    hero_cta_secondary_url: string | null;
    hero_illustration_seed: string | null;
    mission_statement: string | null;
    stats?: Array<{ label: string; value: number; suffix?: string }>;
    home_features?: Array<{ icon: string; title: string; description: string }>;
    academics_programs?: Array<{ icon: string; title: string; description: string }>;
    admission_steps?: Array<{ icon: string; title: string; description: string }>;
    academics_faq?: Array<{ question: string; answer: string }>;
    admissions_faq?: Array<{ question: string; answer: string }>;
    contact_address: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    contact_hours: string | null;
    map_embed_url: string | null;
}

interface LiveStats {
    students: number;
    teachers: number;
}

const page = usePage<{ school: SchoolProps | null; live_stats: LiveStats }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

const defaultPrograms = [
    { icon: 'sprout', title: 'Early Years', description: 'Play-based learning that builds curiosity, language, and social confidence from the start.' },
    { icon: 'book-open', title: 'Primary School', description: 'Strong foundations in literacy and numeracy, with regular check-ins on every child\'s progress.' },
    { icon: 'flask-conical', title: 'Middle School', description: 'Subject-specialist teaching that encourages independent thinking across every core subject.' },
    { icon: 'graduation-cap', title: 'Secondary', description: 'Exam-focused preparation paired with mentoring that keeps every student on track.' },
];

const defaultSteps = [
    { icon: 'send', title: 'Submit an enquiry', description: 'Tell us about your child and the admissions team will reach out within two working days.' },
    { icon: 'calendar', title: 'Visit the campus', description: 'See the classrooms, meet teachers, and get your questions answered in person.' },
    { icon: 'check-circle', title: 'Confirm enrollment', description: 'Complete the paperwork and secure your child\'s place for the coming term.' },
];

const defaultFaq = [
    { question: 'How do I apply for admission?', answer: 'Submit an enquiry through the Admissions section above. Our team will contact you within two business days to guide you through the next steps.' },
    { question: 'What grade levels does the school offer?', answer: 'We offer classes from Early Years through Secondary. See the Academics section for details on each stage.' },
    { question: 'What are the school timings?', answer: 'Classes run Monday to Saturday, morning to early afternoon. Exact timings vary slightly by grade level.' },
    { question: 'Can I visit the campus before applying?', answer: 'Absolutely. Campus visits are part of our admissions process and can also be arranged separately through the Contact section.' },
];

const facilities = [
    { icon: 'trophy', label: 'Sports & Athletics' },
    { icon: 'palette', label: 'Art & Music' },
    { icon: 'mic', label: 'Debate Club' },
    { icon: 'flask-conical', label: 'Science Lab' },
    { icon: 'heart-handshake', label: 'Community Service' },
];

/** Students/Teachers rows use live DB counts, not admin-entered numbers, so they never drift stale. */
const stats = computed(() => {
    const live: Record<string, number> = {
        students: page.props.live_stats?.students ?? 0,
        teachers: page.props.live_stats?.teachers ?? 0,
    };
    const base = school.value?.stats?.length
        ? school.value.stats
        : [
              { label: 'Students', value: 0, suffix: '+' },
              { label: 'Teachers', value: 0, suffix: '+' },
              { label: 'Years of Excellence', value: 20, suffix: '+' },
              { label: 'Classrooms', value: 40, suffix: '+' },
          ];

    return base.map((stat) => {
        const liveValue = live[stat.label.toLowerCase()];
        return liveValue !== undefined ? { ...stat, value: liveValue } : stat;
    });
});

const aboutHighlights = computed(() => [
    { label: 'Years of Excellence', value: '25+' },
    { label: 'Happy Students', value: `${(page.props.live_stats?.students ?? 0) || '1200'}+` },
    { label: 'Awards Won', value: '40+' },
]);

// Neither home_features nor academics_programs are used here: both hold
// data seeded for the old design (home_features uses heroicons-style names
// like "UserGroupIcon"; academics_programs has no `icon` key at all, just
// title/features/description). Until an admin re-enters content in this
// section's actual shape (icon/title/description, lucide icon names), the
// hardcoded default is the only source guaranteed to render correctly.
const programs = computed(() => defaultPrograms);
const admissionSteps = computed(() => (school.value?.admission_steps?.length ? school.value.admission_steps : defaultSteps));
const faqItems = computed(() => {
    const merged = [...(school.value?.academics_faq ?? []), ...(school.value?.admissions_faq ?? [])];
    return merged.length ? merged : defaultFaq;
});
</script>

<template>
    <Head :title="schoolName" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <HeroSection
                :headline="school?.hero_headline ?? `Where every student is known, challenged, and supported`"
                :subtext="school?.hero_subtext ?? 'A caring learning community that helps every child grow into a confident, capable learner.'"
                :primary-cta="{ label: school?.hero_cta_primary_text ?? 'Apply for Admission', href: school?.hero_cta_primary_url ?? '#admissions' }"
                :secondary-cta="{ label: school?.hero_cta_secondary_text ?? 'Explore Academics', href: school?.hero_cta_secondary_url ?? '#academics' }"
                :image-seed="school?.hero_illustration_seed ?? 'school-classroom-main'"
            />

            <StatsBandSection :stats="stats" />

            <AboutSection
                :mission-statement="school?.mission_statement ?? 'To provide a nurturing environment where every student is guided to discover their potential, build strong character, and grow into a confident, responsible citizen.'"
                :highlights="aboutHighlights"
            />

            <ProgramsSection :programs="programs" />

            <CampusLifeSection :facilities="facilities" />

            <GalleryCtaSection />

            <TestimonialsSection />

            <AdmissionsSection :steps="admissionSteps" />

            <FaqSection :items="faqItems" />

            <ContactSection
                :address="school?.contact_address ?? null"
                :phone="school?.contact_phone ?? null"
                :email="school?.contact_email ?? null"
                :hours="school?.contact_hours ?? null"
                :map-embed-url="school?.map_embed_url ?? null"
            />
        </main>

        <PortfolioFooter />
    </div>
</template>
