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
