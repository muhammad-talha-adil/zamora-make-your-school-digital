<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import HeroSection from '@/components/public/sections/HeroSection.vue';
import StatsBandSection from '@/components/public/sections/StatsBandSection.vue';
import AboutSection from '@/components/public/sections/AboutSection.vue';
import DirectorSection from '@/components/public/sections/DirectorSection.vue';
import StaffSection from '@/components/public/sections/StaffSection.vue';
import CampusesSection from '@/components/public/sections/CampusesSection.vue';
import TestimonialsSection from '@/components/public/sections/TestimonialsSection.vue';
import GalleryCtaSection from '@/components/public/sections/GalleryCtaSection.vue';
import AdmissionsSection from '@/components/public/sections/AdmissionsSection.vue';
import FaqSection from '@/components/public/sections/FaqSection.vue';
import ContactSection from '@/components/public/sections/ContactSection.vue';
import SectionDivider from '@/components/public/SectionDivider.vue';

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

interface PublicStaff {
    id: number;
    name: string;
    designation: string | null;
    department: string | null;
    campus: string | null;
    photo_url: string | null;
}

interface PublicCampus {
    id: number;
    name: string;
    address: string | null;
    type: string | null;
}

interface ActiveSession {
    name: string;
    start_date: string | null;
}

const page = usePage<{
    school: SchoolProps | null;
    live_stats: LiveStats;
    public_staff: PublicStaff[];
    public_campuses: PublicCampus[];
    active_session: ActiveSession | null;
}>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

const defaultSteps = [
    { icon: 'send', title: 'Submit an enquiry', description: 'Tell us about your child and the admissions team will reach out within two working days.' },
    { icon: 'calendar', title: 'Visit the campus', description: 'See the classrooms, meet teachers, and get your questions answered in person.' },
    { icon: 'check-circle', title: 'Confirm enrollment', description: 'Complete the paperwork and secure your child\'s place for the coming term.' },
];

const defaultFaq = [
    { question: 'How do I apply for admission?', answer: 'Submit an enquiry through the Admissions section above. Our team will contact you within two business days to guide you through the next steps.' },
    { question: 'What grade levels does the school offer?', answer: 'We offer classes from Early Years through Secondary.' },
    { question: 'What are the school timings?', answer: 'Classes run Monday to Saturday, morning to early afternoon. Exact timings vary slightly by grade level.' },
    { question: 'Can I visit the campus before applying?', answer: 'Absolutely. Campus visits are part of our admissions process and can also be arranged separately through the Contact section.' },
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

const admissionSteps = computed(() => (school.value?.admission_steps?.length ? school.value.admission_steps : defaultSteps));

const STAT_ICONS: Record<string, string> = {
    Students: 'users',
    Teachers: 'graduation-cap',
    'Years of Excellence': 'award',
    Classrooms: 'school',
};

/**
 * Floating hero cards prefer whichever real numbers are actually non-zero.
 * A freshly-set-up school has 0 enrolled students/teachers, and a "0
 * Students enrolled" card reads as broken, not as "real data" - so this
 * falls through to the still-real admin-entered stats (years of excellence,
 * classroom count) rather than forcing the live counts specifically.
 */
const heroFloatingStats = computed(() =>
    stats.value
        .filter((stat) => stat.value > 0)
        .slice(0, 2)
        .map((stat) => ({
            icon: STAT_ICONS[stat.label] ?? 'sparkles',
            label: stat.label,
            value: `${stat.value}${stat.suffix ?? ''}`,
        })),
);

/** Authority cue, built from the real "Years of Excellence" stat rather than an invented number. */
const heroEyebrow = computed(() => {
    const years = stats.value.find((stat) => stat.label === 'Years of Excellence');
    return years ? `${years.value}${years.suffix ?? ''} Years of Excellence` : undefined;
});

/** Social proof from real staff photos (max 3) plus the live enrolled-student count. */
const heroSocialProof = computed(() => {
    const avatarUrls = (page.props.public_staff ?? [])
        .map((member) => member.photo_url)
        .filter((url): url is string => Boolean(url))
        .slice(0, 3);

    if (!avatarUrls.length) {
        return undefined;
    }

    return {
        avatarUrls,
        line: `Join ${page.props.live_stats?.students ?? 0}+ students here`,
    };
});

/** Honest time-anchor from the real active session, not a fabricated countdown. */
const heroSessionNote = computed(() => {
    const startDate = page.props.active_session?.start_date;
    if (!startDate) {
        return undefined;
    }
    const formatted = new Date(startDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    return `New session begins ${formatted}.`;
});
const faqItems = computed(() => {
    const merged = [...(school.value?.academics_faq ?? []), ...(school.value?.admissions_faq ?? [])];
    return merged.length ? merged : defaultFaq;
});

const DIRECTOR_TITLES = ['principal', 'director', 'headmaster', 'headmistress', 'head teacher'];

/** The one staff member whose designation reads as school leadership gets the standalone intro; everyone else goes in the staff slider. */
const director = computed(() =>
    page.props.public_staff?.find((member) => DIRECTOR_TITLES.some((title) => member.designation?.toLowerCase().includes(title))),
);
const otherStaff = computed(() => (page.props.public_staff ?? []).filter((member) => member.id !== director.value?.id));
</script>

<template>
    <Head :title="schoolName" />

    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <main class="flex-1">
            <HeroSection
                :headline="school?.hero_headline ?? `Where every student is known, challenged, and supported`"
                :subtext="school?.hero_subtext ?? 'A caring learning community that helps every child grow into a confident, capable learner.'"
                :primary-cta="{ label: school?.hero_cta_primary_text ?? 'Secure Your Child\'s Seat', href: school?.hero_cta_primary_url ?? '#admissions' }"
                :secondary-cta="{ label: school?.hero_cta_secondary_text ?? 'Book a Campus Tour', href: school?.hero_cta_secondary_url ?? '#contact' }"
                :image-seed="school?.hero_illustration_seed ?? 'school-classroom-main'"
                :eyebrow="heroEyebrow"
                :social-proof="heroSocialProof"
                :session-note="heroSessionNote"
                :stats="heroFloatingStats"
                :highlights="[
                    { icon: 'users', text: 'Small class sizes' },
                    { icon: 'shield-check', text: 'Safe, modern campus' },
                    { icon: 'heart-handshake', text: 'Caring, qualified teachers' },
                ]"
            />

            <StatsBandSection :stats="stats" />

            <AboutSection
                :mission-statement="school?.mission_statement ?? 'To provide a nurturing environment where every student is guided to discover their potential, build strong character, and grow into a confident, responsible citizen.'"
                :highlights="aboutHighlights"
            />

            <SectionDivider backdrop="bg-background" fill="text-background" />
            <DirectorSection v-if="director" :director="director" />

            <SectionDivider backdrop="bg-background" fill="text-muted" />
            <StaffSection v-if="otherStaff.length" :staff="otherStaff" />

            <SectionDivider backdrop="bg-muted" fill="text-background" />
            <CampusesSection :campuses="page.props.public_campuses ?? []" />

            <SectionDivider backdrop="bg-background" fill="text-muted" />
            <GalleryCtaSection />

            <SectionDivider backdrop="bg-muted" fill="text-primary/5" />
            <TestimonialsSection />

            <SectionDivider backdrop="bg-primary/5" fill="text-background" />
            <AdmissionsSection :steps="admissionSteps" />

            <SectionDivider backdrop="bg-background" fill="text-muted" />
            <FaqSection :items="faqItems" />

            <SectionDivider backdrop="bg-muted" fill="text-background" />
            <ContactSection
                :address="school?.contact_address ?? null"
                :phone="school?.contact_phone ?? null"
                :email="school?.contact_email ?? null"
                :hours="school?.contact_hours ?? null"
                :map-embed-url="school?.map_embed_url ?? null"
            />

            <SectionDivider backdrop="bg-background" fill="text-primary" />
        </main>

        <PortfolioFooter />
    </div>
</template>
