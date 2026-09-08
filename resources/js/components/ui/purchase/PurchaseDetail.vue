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
                    <div class="p-2 bg-success/10 rounded-lg">
                        <Icon icon="shopping-cart" class="w-5 h-5 text-success" />
                    </div>
                    Purchase Details
                </DialogTitle>
            </DialogHeader>
            
            <div v-if="purchaseData" class="space-y-4 py-4">
                <!-- Purchase Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Purchase ID</label>
                        <p class="text-foreground font-semibold">#{{ purchaseData.id }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Date</label>
                        <p class="text-foreground">{{ formatDate(purchaseData.purchase_date) }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Campus</label>
                        <p class="text-foreground">{{ purchaseData.campus_name || 'All Campuses' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-muted-foreground">Supplier</label>
                        <p class="text-foreground">{{ purchaseData.supplier?.name || '-' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-muted-foreground">Total Amount</label>
                        <p class="text-xl font-bold text-success">{{ formatCurrency(purchaseData.total_amount) }}</p>
                    </div>
                </div>
                
                <!-- Items -->
                <div v-if="purchaseData.items_count > 0">
                    <label class="text-xs font-medium text-muted-foreground">Items ({{ purchaseData.items_count }})</label>
                    <div class="table-scroll mt-2 border border-border rounded-lg">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Item Name</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground uppercase">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr>
                                    <td colspan="2" class="px-3 py-3 text-sm text-muted-foreground">
                                        {{ purchaseData.item_names }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Note -->
                <div v-if="purchaseData.note">
                    <label class="text-xs font-medium text-muted-foreground">Note</label>
                    <p class="text-foreground text-sm">{{ purchaseData.note }}</p>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-wrap justify-end gap-2 pt-4 border-t border-border">
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
            
            <div v-else class="py-8 text-center text-muted-foreground">
                No purchase data available
            </div>
        </DialogContent>
    </Dialog>
</template>
