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
    <Head :title="`${schoolName} - Home`" />
    <div class="flex min-h-screen flex-col bg-background">
        <PortfolioHeader />

        <!-- Main Content -->
        <main class="flex-1 pt-20 pb-20">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <p class="text-center text-2xl text-foreground">
                    This is Home page
                </p>
                <!-- Add more content to make it scrollable -->
                <div class="mt-10 space-y-10">
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 1
                        </p>
                    </div>
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 2
                        </p>
                    </div>
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 3
                        </p>
                    </div>
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 4
                        </p>
                    </div>
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 5
                        </p>
                    </div>
                    <div
                        class="flex h-96 items-center justify-center rounded-lg bg-muted"
                    >
                        <p class="text-muted-foreground">
                            Placeholder Content 6
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <PortfolioFooter />
    </div>
</template>

