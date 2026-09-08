<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

// Props
interface Props {
    months: Array<{
        id: number;
        name: string;
        month_number: number;
    }>;
    trigger?: string;
}

withDefaults(defineProps<Props>(), {
    trigger: 'View Months',
});

// Dialog
const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button>{{ trigger }}</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Months</DialogTitle>
                <DialogDescription>
                    List of all months in the calendar year.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Table -->
                <div class="space-y-4">
                    <div class="bg-card rounded-lg border border-border overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">#</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Month Number</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Name</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-card divide-y divide-border">
                                    <tr v-for="(month, index) in months" :key="month.id" class="hover:bg-accent transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-muted-foreground">{{ index + 1 }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-muted-foreground">{{ month.month_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-foreground">{{ month.name }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
