<template>
    <div class="bg-card p-6 rounded-lg shadow-lg border border-border">
        <h2 class="text-xl font-semibold text-card-foreground mb-4">Recent Activity</h2>
        <div class="space-y-3">
            <div v-for="activity in activities" :key="activity.id" class="flex items-center space-x-3">
                <div class="w-2 h-2 bg-primary rounded-full"></div>
                <p class="text-sm text-muted-foreground">{{ activity.description }}</p>
                <span class="text-xs text-muted-foreground">{{ activity.time }}</span>
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
