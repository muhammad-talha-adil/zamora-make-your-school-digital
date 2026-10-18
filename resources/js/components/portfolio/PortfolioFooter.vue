<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useLenis } from '@/composables/useLenis';

interface SchoolProps {
    name: string;
    contact_address: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    social_links: Array<{ platform: string; url: string }> | null;
}

const page = usePage<{ school: SchoolProps | null; name: string }>();
const school = computed(() => page.props.school);
const schoolName = computed(() => school.value?.name ?? page.props.name);
const year = new Date().getFullYear();

const socialIcon: Record<string, string> = {
    facebook: 'facebook',
    instagram: 'instagram',
    threads: 'threads',
    x: 'x',
};

/** social_links is stored as an array of {platform, url} (School::casts()), not a plain object map. */
const socialLinks = computed(() => (school.value?.social_links ?? []).filter((link) => link.url));

const quickLinks = [
    { label: 'About', href: '#about' },
    { label: 'Admissions', href: '#admissions' },
    { label: 'Contact', href: '#contact' },
];

const { scrollTo } = useLenis();
</script>

<template>
    <!-- Always a dark surface (bg-sidebar), never bg-primary directly: an admin-chosen light/bright
         primary color would otherwise leave the hardcoded white text below unreadable. The primary
         color still shows through as a brand-tinted gradient wash. -->
    <footer class="relative overflow-hidden bg-linear-to-br from-sidebar via-sidebar to-primary/30">
        <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/20 via-transparent to-transparent" aria-hidden="true" />

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-10 divide-y divide-white/10 md:grid-cols-[1.4fr_1fr_1fr] md:divide-x md:divide-y-0">
                <div class="md:pr-10">
                    <h3 class="text-lg font-semibold text-white">{{ schoolName }}</h3>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-white/70">
                        A nurturing learning community preparing students for every stage ahead, from first lessons to lifelong goals.
                    </p>
                    <div v-if="socialLinks.length" class="mt-5 flex gap-3">
                        <a
                            v-for="link in socialLinks"
                            :key="link.platform"
                            :href="link.url"
                            target="_blank"
                            rel="noopener"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-white/5 text-white/70 backdrop-blur-sm transition-colors hover:border-white hover:text-white"
                        >
                            <Icon :icon="socialIcon[link.platform] ?? 'link'" class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div class="pt-10 md:pt-0 md:px-10">
                    <h4 class="text-sm font-semibold text-white">Quick Links</h4>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in quickLinks" :key="link.href">
                            <button
                                type="button"
                                class="text-sm text-white/70 transition-colors hover:text-white"
                                @click="scrollTo(link.href)"
                            >
                                {{ link.label }}
                            </button>
                        </li>
                        <li>
                            <Link href="/terms" class="text-sm text-white/70 transition-colors hover:text-white">Terms of Service</Link>
                        </li>
                        <li>
                            <Link href="/privacy" class="text-sm text-white/70 transition-colors hover:text-white">Privacy Policy</Link>
                        </li>
                    </ul>
                </div>

                <div class="pt-10 md:pt-0 md:pl-10">
                    <h4 class="text-sm font-semibold text-white">Get in Touch</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-white/70">
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

            <div class="mt-12 border-t border-white/15 pt-6 text-center text-xs text-white/60">
                Copyright {{ year }} {{ schoolName }}. All rights reserved.
            </div>
        </div>
    </footer>
</template>
