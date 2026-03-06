<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const schoolName = computed(() => page.props.name);
</script>

<template>
    <footer class="bg-muted py-8 text-muted-foreground">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ new Date().getFullYear() }} {{ schoolName }}. All rights reserved.</p>
        </div>
    </footer>
</template>
