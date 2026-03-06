<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Error Code -->
            <div class="relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-32 h-32 rounded-full bg-red-100 dark:bg-red-900/30 animate-pulse"></div>
                </div>
                <div class="relative z-10 flex items-center justify-center w-32 h-32 mx-auto">
                    <Icon icon="shield-x" class="w-16 h-16 text-red-600 dark:text-red-400" />
                </div>
            </div>

            <!-- Error Content -->
            <div class="space-y-4">
                <h1 class="text-6xl font-bold text-gray-900 dark:text-white">403</h1>
                <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300">Access Denied</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    You do not have permission to access this page. 
                    This may be due to insufficient privileges or an invalid authorization token.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                <Button @click="goHome" variant="outline">
                    <Icon icon="home" class="mr-2 h-4 w-4" />
                    Go Home
                </Button>
                <Button @click="goBack" variant="default">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Go Back
                </Button>
            </div>

            <!-- Help -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Need access? 
                    <a href="/contact" class="text-blue-600 dark:text-blue-400 hover:underline">Contact Administrator</a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';

const goHome = () => {
    window.location.href = route('home');
};

const goBack = () => {
    window.history.back();
};
</script>
