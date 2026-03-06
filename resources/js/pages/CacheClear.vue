<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Cache Clear" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Cache Management
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Clear and rebuild application caches
                    </p>
                </div>
            </div>

            <!-- Cache Clear Options -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Clear All Cache -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <Icon icon="trash-2" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Clear All Caches</h2>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Clears all frontend and backend caches including browser cache instructions.
                    </p>
                    <form @submit.prevent="clearAll">
                        <Button type="submit" variant="destructive" class="w-full">
                            <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Clear All Caches
                        </Button>
                    </form>
                </div>

                <!-- Clear Backend Cache -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <Icon icon="server" class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Clear Backend</h2>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Clears Laravel backend caches (config, routes, views, events, etc.).
                    </p>
                    <form @submit.prevent="clearBackend">
                        <Button type="submit" variant="outline" class="w-full">
                            <Icon v-if="processingBackend" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Clear Backend Cache
                        </Button>
                    </form>
                </div>

                <!-- Clear Frontend Cache -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <Icon icon="globe" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Clear Frontend</h2>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Clears browser cache instructions. Requires hard refresh (Ctrl+F5).
                    </p>
                    <form @submit.prevent="clearFrontend">
                        <Button type="submit" variant="outline" class="w-full">
                            <Icon v-if="processingFrontend" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Clear Frontend Cache
                        </Button>
                    </form>
                </div>

                <!-- Rebuild Cache -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <Icon icon="refresh-cw" class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rebuild Cache</h2>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Rebuilds optimized caches for better performance.
                    </p>
                    <form @submit.prevent="rebuild">
                        <Button type="submit" variant="secondary" class="w-full">
                            <Icon v-if="processingRebuild" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Rebuild Cache
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div v-if="showResults" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Command Results</h2>
                <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto text-sm">{{ resultsOutput }}</pre>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <Icon icon="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
                    <span class="text-green-700 dark:text-green-300">{{ successMessage }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cache Management', href: '/cache' },
];

const processing = ref(false);
const processingBackend = ref(false);
const processingFrontend = ref(false);
const processingRebuild = ref(false);
const showResults = ref(false);
const resultsOutput = ref('');
const successMessage = ref('');

const clearAll = () => {
    processing.value = true;
    showResults.value = false;
    
    router.post(route('cache.clear'), {}, {
        onFinish: () => {
            processing.value = false;
            successMessage.value = 'All caches cleared successfully!';
            showResults.value = true;
            
            // Check for results in flash data
            const pageProps = (window as any).pageProps || {};
            if (pageProps.cacheResults) {
                resultsOutput.value = JSON.stringify(pageProps.cacheResults, null, 2);
            }
        },
    });
};

const clearBackend = () => {
    processingBackend.value = true;
    showResults.value = false;
    
    router.post(route('cache.clear.backend'), {}, {
        onFinish: () => {
            processingBackend.value = false;
            successMessage.value = 'Backend caches cleared successfully!';
            showResults.value = true;
            
            const pageProps = (window as any).pageProps || {};
            if (pageProps.cacheResults) {
                resultsOutput.value = JSON.stringify(pageProps.cacheResults, null, 2);
            }
        },
    });
};

const clearFrontend = () => {
    processingFrontend.value = true;
    
    router.post(route('cache.clear.frontend'), {}, {
        onSuccess: () => {
            processingFrontend.value = false;
            successMessage.value = 'Frontend cache cleared! Please hard refresh your browser (Ctrl+F5).';
        },
    });
};

const rebuild = () => {
    processingRebuild.value = true;
    showResults.value = false;
    
    router.post(route('cache.rebuild'), {}, {
        onFinish: () => {
            processingRebuild.value = false;
            successMessage.value = 'Caches rebuilt successfully!';
            showResults.value = true;
            
            const pageProps = (window as any).pageProps || {};
            if (pageProps.cacheResults) {
                resultsOutput.value = JSON.stringify(pageProps.cacheResults, null, 2);
            }
        },
    });
};
</script>
