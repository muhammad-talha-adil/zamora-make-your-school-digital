<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';
import PortfolioFooter from '@/components/portfolio/PortfolioFooter.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const schoolName = computed(() => page.props.name);
</script>

<template>
    <Head :title="`${schoolName} - Contact`" />
    <div class="min-h-screen flex flex-col bg-background">
        <PortfolioHeader />

        <!-- Main Content -->
        <main class="flex-1 pt-20 pb-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <p class="text-center text-2xl text-foreground">This is Contact page</p>
            </div>
        </main>

        <PortfolioFooter />
    </div>
</template>
