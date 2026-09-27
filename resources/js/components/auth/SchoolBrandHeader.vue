<script setup lang="ts">
import { computed } from 'vue';

interface School {
    name: string;
    logo_path?: string;
    tagline?: string;
}

type Variant = 'left-panel' | 'form-header';

const props = defineProps<{
    school?: School;
    variant?: Variant;
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

const isLeftPanel = computed(() => props.variant === 'left-panel');
const isFormHeader = computed(() => props.variant === 'form-header' || !props.variant);
</script>

<template>
    <div :class="[
        'text-center',
        isLeftPanel ? 'mb-8' : 'mb-6',
        isFormHeader ? '' : '',
    ]">
        <div class="flex justify-center mb-4">
            <img
                :src="schoolLogo"
                :alt="schoolName + ' logo'"
                :class="[
                    'rounded-full shadow-lg border-2 border-border transition-transform duration-300 hover:scale-105',
                    isLeftPanel ? 'h-12 w-12' : 'h-10 w-10',
                ]"
            />
        </div>
        <h1 :class="[isLeftPanel ? 'text-2xl' : 'text-xl', 'font-bold text-foreground mb-1']">
            {{ schoolName }}
        </h1>
        <p :class="[isLeftPanel ? 'text-lg' : 'text-sm', 'text-muted-foreground']">
            {{ schoolTagline }}
        </p>
    </div>
</template>