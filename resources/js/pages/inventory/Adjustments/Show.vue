<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap gap-2 items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link
          :href="route('inventory.adjustments.index')"
          class="text-muted-foreground hover:text-foreground"
        >
          ← Back
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-foreground">Stock Adjustment Details</h1>
          <p class="text-muted-foreground">{{ adjustment.id }}</p>
        </div>
      </div>
    </div>

    <!-- Adjustment Details -->
    <div class="bg-card rounded-lg shadow-sm p-6">
      <div class="space-y-4">
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Adjustment ID</span>
          <span class="text-sm font-medium text-foreground">#{{ adjustment.id }}</span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Date</span>
          <span class="text-sm font-medium text-foreground">
            {{ new Date(adjustment.created_at).toLocaleString() }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Campus</span>
          <span class="text-sm font-medium text-foreground">{{ adjustment.campus?.name }}</span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Item</span>
          <span class="text-sm font-medium text-foreground">{{ adjustment.inventory_item?.name }}</span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Type</span>
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded-full',
              adjustment.type === 'add' ? 'bg-success/10 text-success' :
              adjustment.type === 'subtract' ? 'bg-destructive/10 text-destructive' :
              'bg-primary/10 text-primary'
            ]"
          >
            {{ adjustment.type === 'add' ? 'Add Stock' : adjustment.type === 'subtract' ? 'Subtract Stock' : 'Set Quantity' }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Quantity Change</span>
          <div class="text-right">
            <span v-if="adjustment.type === 'add'" class="text-success font-bold">
              +{{ adjustment.quantity }}
            </span>
            <span v-else-if="adjustment.type === 'subtract'" class="text-destructive font-bold">
              -{{ adjustment.quantity }}
            </span>
            <span v-else class="text-primary font-bold">
              → {{ adjustment.quantity }}
            </span>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Previous Quantity</span>
          <span class="text-sm font-medium text-foreground">
            {{ adjustment.previous_quantity ?? 'N/A' }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">New Quantity</span>
          <span class="text-sm font-medium text-foreground">
            {{ adjustment.new_quantity ?? 'N/A' }}
          </span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Reason</span>
          <span class="text-sm font-medium text-foreground">{{ adjustment.reason }}</span>
        </div>
        <div v-if="adjustment.reference_number" class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Reference</span>
          <span class="text-sm font-medium text-foreground">{{ adjustment.reference_number }}</span>
        </div>
        <div class="flex flex-wrap gap-2 items-center justify-between pb-4 border-b">
          <span class="text-sm text-muted-foreground">Created By</span>
          <span class="text-sm font-medium text-foreground">{{ adjustment.user?.name || 'System' }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap justify-end gap-3">
      <Link
        :href="route('inventory.adjustments.index')"
        class="px-4 py-2 border border-border rounded-lg text-muted-foreground hover:bg-accent"
      >
        Back to List
      </Link>
      <button
        v-if="canDelete"
        @click="deleteAdjustment"
        class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90"
      >
        Delete Adjustment
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

const props = defineProps({
  adjustment: {
    type: Object,
    required: true,
  },
})

const canDelete = computed(() => {
  // Allow deletion within 24 hours
  return new Date() - new Date(props.adjustment.created_at) < 24 * 60 * 60 * 1000
})

const deleteAdjustment = () => {
  if (confirm('Are you sure you want to delete this adjustment? This will revert the stock changes.')) {
    router.delete(route('inventory.adjustments.destroy', props.adjustment.id), {
      onSuccess: () => router.visit(route('inventory.adjustments.index')),
    })
  }
}
</script>
