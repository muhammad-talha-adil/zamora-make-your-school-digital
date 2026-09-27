<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import { TwitterIcon, FacebookIcon, LinkedinIcon, InstagramIcon, YoutubeIcon } from 'lucide-vue-next';

interface School {
    name?: string;
    slogan?: string | null;
    logo_path?: string | null;
    social_links?: Array<{ platform: string; url: string }>;
    contact_address?: string | null;
    contact_phone?: string | null;
    contact_email?: string | null;
}

const socialIcons: Record<string, any> = {
    facebook: FacebookIcon,
    twitter: TwitterIcon,
    instagram: InstagramIcon,
    linkedin: LinkedinIcon,
    youtube: YoutubeIcon,
};

const page = usePage();
const school = computed(() => page.props.school as School | null);
const schoolName = computed(() => school.value?.name ?? (page.props.name as string));

const footerLinks = {
    school: [
        { name: 'About Us', href: route('about') },
        { name: 'Academics', href: route('academics') },
        { name: 'Admissions', href: route('admissions') },
        { name: 'Contact', href: route('contact') },
    ],
    legal: [
        { name: 'Privacy', href: route('privacy') },
        { name: 'Terms', href: route('terms') },
    ],
};

const socialLinks = computed(() =>
    (school.value?.social_links ?? [])
        .filter((link) => socialIcons[link.platform])
        .map((link) => ({ name: link.platform, icon: socialIcons[link.platform], href: link.url })),
);
</script>

<template>
    <footer class="bg-muted/30 border-t border-border" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Brand -->
                <div class="md:col-span-1 space-y-4">
                    <div class="flex items-center">
                        <img :src="page.props.school?.logo_path || '/sample-logo.png'" alt="" class="h-8 w-8 mr-2" aria-hidden="true" />
                        <span class="text-xl font-bold text-foreground">{{ schoolName }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground max-w-xs leading-relaxed">
                        {{ school?.slogan ?? 'Nurturing students with strong academics, character, and community.' }}
                    </p>
                    <div v-if="socialLinks.length" class="flex space-x-6">
                        <a v-for="social in socialLinks" :key="social.name" :href="social.href" target="_blank" rel="noopener noreferrer"
                           class="text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded"
                           :aria-label="social.name">
                            <component :is="social.icon" class="h-5 w-5" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <!-- School Links -->
                <nav aria-label="School links">
                    <h3 class="text-sm font-semibold text-foreground mb-4">School</h3>
                    <ul class="space-y-3">
                        <li v-for="link in footerLinks.school" :key="link.name">
                            <Link :href="link.href" class="text-sm text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded">
                                {{ link.name }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <!-- Contact -->
                <div v-if="school?.contact_address || school?.contact_phone || school?.contact_email">
                    <h3 class="text-sm font-semibold text-foreground mb-4">Contact</h3>
                    <ul class="space-y-3 text-sm text-muted-foreground">
                        <li v-if="school?.contact_address">{{ school.contact_address }}</li>
                        <li v-if="school?.contact_phone">
                            <a :href="`tel:${school.contact_phone.replace(/[^+\d]/g, '')}`" class="hover:text-primary transition-colors">{{ school.contact_phone }}</a>
                        </li>
                        <li v-if="school?.contact_email">
                            <a :href="`mailto:${school.contact_email}`" class="hover:text-primary transition-colors">{{ school.contact_email }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-border pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} {{ schoolName }}. All rights reserved.
                </p>

                <nav aria-label="Legal links" class="flex flex-wrap items-center justify-center md:justify-end gap-6">
                    <Link v-for="link in footerLinks.legal" :key="link.name" :href="link.href"
                          class="text-sm text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded">
                        {{ link.name }}
                    </Link>
                </nav>
            </div>
        </div>
    </footer>
</template>