<template>
    <div class="bg-card p-5 md:p-6 rounded-xl border border-border shadow-sm">
        <h2 class="text-lg font-semibold text-foreground mb-4">Recent Activity</h2>
        <div class="space-y-1">
            <div
                v-for="activity in activities"
                :key="activity.id"
                class="flex items-center gap-3 py-2 rounded-lg hover:bg-muted/30 transition-colors"
            >
                <div class="w-2 h-2 shrink-0 bg-primary rounded-full"></div>
                <p class="flex-1 min-w-0 text-sm text-muted-foreground truncate">{{ activity.description }}</p>
                <span class="text-xs text-muted-foreground shrink-0">{{ activity.time }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Activity {
    id: number;
    description: string;
    time: string;
}

interface Props {
    activities: Activity[];
}

defineProps<Props>();
</script>
