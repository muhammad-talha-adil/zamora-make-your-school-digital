<script setup lang="ts">
import { computed } from 'vue';

interface School {
    name: string;
    logo_path?: string;
    tagline?: string;
}

const props = defineProps<{
    school?: School;
}>();

const schoolName = computed(() => {
    return props.school?.name || 'School Management System';
});

const schoolTagline = computed(() => {
    return props.school?.tagline || 'Secure access to your educational platform';
});

const schoolLogo = computed(() => {
    return props.school?.logo_path || '/sample-logo.png';
});
</script>

<template>
    <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
            <img
                :src="schoolLogo"
                :alt="schoolName + ' logo'"
                class="w-16 h-16 rounded-full shadow-lg border-2 border-border"
            />
        </div>
        <h1 class="text-3xl font-bold text-foreground mb-2">{{ schoolName }}</h1>
        <p class="text-muted-foreground text-lg">{{ schoolTagline }}</p>
    </div>
</template>
