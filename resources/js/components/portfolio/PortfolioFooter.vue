<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

interface SchoolProps {
    name: string;
    contact_address: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    social_links: Record<string, string> | null;
}

const page = usePage<{ school: SchoolProps | null; name: string }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? page.props.name);
const year = new Date().getFullYear();

const socialIcon: Record<string, string> = {
    facebook: 'facebook',
    instagram: 'instagram',
    twitter: 'twitter',
    youtube: 'youtube',
    linkedin: 'linkedin',
};

const socialLinks = computed(() => Object.entries(school.value?.social_links ?? {}).filter(([, url]) => !!url));

const quickLinks = [
    { label: 'About', href: '#about' },
    { label: 'Staff', href: '#staff' },
    { label: 'Campuses', href: '#campuses' },
    { label: 'Admissions', href: '#admissions' },
    { label: 'Contact', href: '#contact' },
];

const scrollTo = (hash: string) => {
    document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <footer class="bg-primary">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <h3 class="text-lg font-semibold text-primary-foreground">{{ schoolName }}</h3>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-primary-foreground/70">
                        A nurturing learning community preparing students for every stage ahead, from first lessons to lifelong goals.
                    </p>
                    <div v-if="socialLinks.length" class="mt-5 flex gap-3">
                        <a
                            v-for="[platform, url] in socialLinks"
                            :key="platform"
                            :href="url"
                            target="_blank"
                            rel="noopener"
                            class="flex h-9 w-9 items-center justify-center rounded-md border border-primary-foreground/20 text-primary-foreground/70 transition-colors hover:border-primary-foreground hover:text-primary-foreground"
                        >
                            <Icon :icon="socialIcon[platform] ?? 'link'" class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-primary-foreground">Quick Links</h4>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in quickLinks" :key="link.href">
                            <button
                                type="button"
                                class="text-sm text-primary-foreground/70 transition-colors hover:text-primary-foreground"
                                @click="scrollTo(link.href)"
                            >
                                {{ link.label }}
                            </button>
                        </li>
                        <li>
                            <Link href="/terms" class="text-sm text-primary-foreground/70 transition-colors hover:text-primary-foreground">Terms of Service</Link>
                        </li>
                        <li>
                            <Link href="/privacy" class="text-sm text-primary-foreground/70 transition-colors hover:text-primary-foreground">Privacy Policy</Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-primary-foreground">Get in Touch</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-primary-foreground/70">
                        <li v-if="school?.contact_address" class="flex gap-2">
                            <Icon icon="map-pin" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ school.contact_address }}</span>
                        </li>
                        <li v-if="school?.contact_phone" class="flex gap-2">
                            <Icon icon="phone" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ school.contact_phone }}</span>
                        </li>
                        <li v-if="school?.contact_email" class="flex gap-2">
                            <Icon icon="mail" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ school.contact_email }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-primary-foreground/15 pt-6 text-center text-xs text-primary-foreground/60">
                Copyright {{ year }} {{ schoolName }}. All rights reserved.
            </div>
        </div>
    </footer>
</template>
