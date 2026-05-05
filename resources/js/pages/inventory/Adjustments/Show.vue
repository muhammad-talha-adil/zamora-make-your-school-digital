<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link
          :href="route('inventory.adjustments.index')"
          class="text-gray-500 hover:text-gray-700"
        >
          ← Back
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Stock Adjustment Details</h1>
          <p class="text-gray-500">{{ adjustment.id }}</p>
        </div>
      </div>
    </div>

    <!-- Adjustment Details -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="space-y-4">
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Adjustment ID</span>
          <span class="text-sm font-medium text-gray-900">#{{ adjustment.id }}</span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Date</span>
          <span class="text-sm font-medium text-gray-900">
            {{ new Date(adjustment.created_at).toLocaleString() }}
          </span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Campus</span>
          <span class="text-sm font-medium text-gray-900">{{ adjustment.campus?.name }}</span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Item</span>
          <span class="text-sm font-medium text-gray-900">{{ adjustment.inventory_item?.name }}</span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Type</span>
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded-full',
              adjustment.type === 'add' ? 'bg-green-100 text-green-800' :
              adjustment.type === 'subtract' ? 'bg-red-100 text-red-800' :
              'bg-blue-100 text-blue-800'
            ]"
          >
            {{ adjustment.type === 'add' ? 'Add Stock' : adjustment.type === 'subtract' ? 'Subtract Stock' : 'Set Quantity' }}
          </span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Quantity Change</span>
          <div class="text-right">
            <span v-if="adjustment.type === 'add'" class="text-green-600 font-bold">
              +{{ adjustment.quantity }}
            </span>
            <span v-else-if="adjustment.type === 'subtract'" class="text-red-600 font-bold">
              -{{ adjustment.quantity }}
            </span>
            <span v-else class="text-blue-600 font-bold">
              → {{ adjustment.quantity }}
            </span>
          </div>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Previous Quantity</span>
          <span class="text-sm font-medium text-gray-900">
            {{ adjustment.previous_quantity ?? 'N/A' }}
          </span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">New Quantity</span>
          <span class="text-sm font-medium text-gray-900">
            {{ adjustment.new_quantity ?? 'N/A' }}
          </span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Reason</span>
          <span class="text-sm font-medium text-gray-900">{{ adjustment.reason }}</span>
        </div>
        <div v-if="adjustment.reference_number" class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Reference</span>
          <span class="text-sm font-medium text-gray-900">{{ adjustment.reference_number }}</span>
        </div>
        <div class="flex items-center justify-between pb-4 border-b">
          <span class="text-sm text-gray-500">Created By</span>
          <span class="text-sm font-medium text-gray-900">{{ adjustment.user?.name || 'System' }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3">
      <Link
        :href="route('inventory.adjustments.index')"
        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
      >
        Back to List
      </Link>
      <button
        v-if="canDelete"
        @click="deleteAdjustment"
        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
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
