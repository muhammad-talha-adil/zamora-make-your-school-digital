import { AppPageProps } from '@/types/index';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        glob(pattern: string): Record<string, () => unknown>;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
        route: typeof import('ziggy-js').route;
    }
}

/**
 * Global route function declaration for Ziggy
 * This makes the route function available globally without importing
 * Provided by Laravel's @routes directive
 */
declare function route(
    name: string,
    params?: string | number | boolean | null | undefined | Record<string, string | number | boolean | null | undefined> | Array<string | number | boolean | null | undefined | Record<string, string | number | boolean | null | undefined>>,
    absolute?: boolean
): string;
