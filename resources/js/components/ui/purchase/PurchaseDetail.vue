<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { formatDate, formatCurrency } from '@/utils';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    purchase: any;
    open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'edit': [purchase: any];
    'delete': [purchase: any];
}>();

const isOpen = ref(props.open);

watch(() => props.open, (newVal) => {
    isOpen.value = newVal;
});

watch(isOpen, (newVal) => {
    emit('update:open', newVal);
});

const purchaseData = computed(() => props.purchase);

const close = () => {
    isOpen.value = false;
};

const handleEdit = () => {
    emit('edit', props.purchase);
    close();
};

const handleDelete = () => {
    emit('delete', props.purchase);
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <Icon icon="shopping-cart" class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    Purchase Details
                </DialogTitle>
            </DialogHeader>
            
            <div v-if="purchaseData" class="space-y-4 py-4">
                <!-- Purchase Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Purchase ID</label>
                        <p class="text-gray-900 dark:text-white font-semibold">#{{ purchaseData.id }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Date</label>
                        <p class="text-gray-900 dark:text-white">{{ formatDate(purchaseData.purchase_date) }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Campus</label>
                        <p class="text-gray-900 dark:text-white">{{ purchaseData.campus_name || 'All Campuses' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Supplier</label>
                        <p class="text-gray-900 dark:text-white">{{ purchaseData.supplier?.name || '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Amount</label>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(purchaseData.total_amount) }}</p>
                    </div>
                </div>
                
                <!-- Items -->
                <div v-if="purchaseData.items_count > 0">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Items ({{ purchaseData.items_count }})</label>
                    <div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Item Name</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr>
                                    <td colspan="2" class="px-3 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ purchaseData.item_names }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Note -->
                <div v-if="purchaseData.note">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Note</label>
                    <p class="text-gray-900 dark:text-white text-sm">{{ purchaseData.note }}</p>
                </div>
                
                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button variant="destructive" size="sm" @click="handleDelete">
                        <Icon icon="trash-2" class="w-4 h-4 mr-1" />
                        Delete
                    </Button>
                    <Button variant="default" size="sm" @click="handleEdit">
                        <Icon icon="edit" class="w-4 h-4 mr-1" />
                        Edit
                    </Button>
                </div>
            </div>
            
            <div v-else class="py-8 text-center text-gray-500">
                No purchase data available
            </div>
        </DialogContent>
    </Dialog>
</template>
