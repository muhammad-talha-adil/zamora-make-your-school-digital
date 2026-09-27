import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface School {
    id: number;
    name: string;
    logo_path?: string;
    tagline?: string;
    hero_headline?: string;
    hero_subtext?: string;
    hero_cta_primary_text?: string;
    hero_cta_primary_url?: string;
    hero_cta_secondary_text?: string;
    hero_cta_secondary_url?: string;
    hero_illustration_seed?: string;
    trust_logos?: Array<{name: string; logo_url: string; url: string}>;
    mission_statement?: string;
    vision_statement?: string;
    values?: Array<{icon: string; title: string; description: string}>;
    leadership_team?: Array<{name: string; role: string; bio: string; photo_url: string}>;
    stats?: Array<{label: string; value: number | string; suffix: string}>;
    history_timeline?: Array<{year: string; title: string; description: string}>;
    contact_address?: string;
    contact_phone?: string;
    contact_email?: string;
    contact_hours?: string;
    map_embed_url?: string;
    social_links?: Array<{platform: string; url: string; icon: string}>;
    meta_title?: string;
    meta_description?: string;
    og_image_path?: string;
    [key: string]: unknown;
}

export interface NavItem {
    id?: string | number;
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: string | LucideIcon;
    isActive?: boolean;
}

export interface MenuItem extends NavItem {
    children?: MenuItem[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    school?: School;
    auth: Auth;
    sidebarOpen: boolean;
    themes: Record<string, unknown>;
    theme_mode: string;
    menus: {
        main: MenuItem[];
        footer: MenuItem[];
    };
    subscriptionWarning: {
        daysRemaining: number;
        status: string;
    } | null;
    academicScope: {
        campusId: number | null;
        isCampusRestricted: boolean;
        activeSessionId: number | null;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type BreadcrumbItemType = BreadcrumbItem;
