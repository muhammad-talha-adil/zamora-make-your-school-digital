<template>
    <SchoolErrorPage :status="409" :message="props.message" />
</template>

<script setup lang="ts">
import SchoolErrorPage from '@/components/errors/SchoolErrorPage.vue';

const props = defineProps<{ message?: string | null }>();
</script>
