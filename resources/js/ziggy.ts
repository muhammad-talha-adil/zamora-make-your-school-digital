/**
 * Ziggy Type Definitions
 * This file provides TypeScript support for the Ziggy route helper
 */

// Import route function from ziggy-js npm package
import { route } from 'ziggy-js';

// Ziggy configuration interface
export interface ZiggyConfig {
    url: string;
    port: number | null;
    defaults: Record<string, unknown>;
    routes: Record<string, {
        uri: string;
        methods: string[];
        domain: string | null;
        name: string;
        action: Record<string, unknown>;
        wheres: Record<string, string>;
    }>;
}

// Bundle the Ziggy configuration for SSR and build time
// This will be replaced by actual routes during build via artisan ziggy:generate
export const Ziggy: ZiggyConfig = {
    url: 'http://localhost:8000',
    port: 8000,
    defaults: {},
    routes: {}
};

// Make route function available globally
if (typeof window !== 'undefined') {
    (window as unknown as { route: typeof route }).route = route;
}

// Re-export route function for convenience
export { route };

// Export type for route parameters
export type RouteParams = string | number | boolean | null | undefined | Record<string, string | number | boolean | null | undefined>;
