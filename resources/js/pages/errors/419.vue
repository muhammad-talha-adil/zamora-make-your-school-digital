<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Code -->
            <div class="relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-32 h-32 rounded-full bg-yellow-100 dark:bg-yellow-900/30 animate-pulse"></div>
                </div>
                <div class="relative z-10 flex items-center justify-center w-32 h-32 mx-auto">
                    <Icon icon="shield-alert" class="w-16 h-16 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>

            <!-- Error Content -->
            <div class="space-y-4">
                <h1 class="text-6xl font-bold text-gray-900 dark:text-white">419</h1>
                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300">Page Expired</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Your session has expired or the page you're trying to access is no longer valid.
                    This usually happens when:
                </p>
                
                <!-- Common Causes -->
                <div class="mt-4 text-left bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <Icon icon="check" class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                            <span>You were inactive for too long</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <Icon icon="check" class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                            <span>You clicked the back button after logging out</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <Icon icon="check" class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                            <span>The form submission timed out</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <Icon icon="check" class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                            <span>Browser cookies were cleared</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                <Button @click="reloadPage" variant="outline">
                    <Icon icon="refresh-cw" class="mr-2 h-4 w-4" />
                    Reload Page
                </Button>
                <Button @click="goHome" variant="default">
                    <Icon icon="home" class="mr-2 h-4 w-4" />
                    Go Home
                </Button>
            </div>

            <!-- Login Prompt -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    If you were logged in, you may need to 
                    <a href="/login" class="text-blue-600 dark:text-blue-400 hover:underline">log in again</a>.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';

const reloadPage = () => {
    window.location.reload();
};

const goHome = () => {
    window.location.href = route('home');
};
</script>
