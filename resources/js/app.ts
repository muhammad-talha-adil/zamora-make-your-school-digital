import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { initializeTheme } from './composables/useAppearance';
import { route } from 'ziggy-js';
import './ziggy'; // Import for window.route setup

// Falls back to the build-time env name until the shared `name` prop (the
// school's own configured name, from HandleInertiaRequests) arrives on the
// first page load, then tracks it across every Inertia navigation.
let appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Create a Vue plugin to inject route into all components
const routePlugin = {
    install(app: any) {
        app.config.globalProperties.route = route;
        app.provide('route', route);
    },
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        appName = (props.initialPage.props as { name?: string }).name || appName;
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(routePlugin)
            .mount(el);
    },
    progress: {
        // Inertia writes this straight into an inline style, so the token has
        // to be resolved here rather than passed through as a var() reference.
        color:
            getComputedStyle(document.documentElement)
                .getPropertyValue('--primary')
                .trim() || '#2563eb',
    },
});

router.on('navigate', (event) => {
    const name = (event.detail.page.props as { name?: string }).name;
    if (name) {
        appName = name;
    }
});

// This will set light / dark mode on page load...
initializeTheme();
