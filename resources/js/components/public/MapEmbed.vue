<script setup lang="ts">
defineProps<{ embedUrl: string | null }>();
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-border bg-muted">
        <iframe
            v-if="embedUrl"
            :src="embedUrl"
            class="h-full min-h-[320px] w-full"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        />
        <div v-else class="flex min-h-[320px] items-center justify-center text-sm text-muted-foreground">
            Map coming soon
        </div>
    </div>
</template>
