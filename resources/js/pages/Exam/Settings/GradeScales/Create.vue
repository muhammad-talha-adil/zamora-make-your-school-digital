<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  name: '',
  rounding_mode: 'round',
  precision: 2,
  is_default: false
})

const saving = ref(false)

const save = () => {
  saving.value = true
  router.post(route('exam.grade-scales.store'), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      saving.value = false
      router.get(route('exam.settings.grade-scales-page'))
    },
    onError: () => {
      saving.value = false
    }
  })
}

const cancel = () => {
  router.get(route('exam.settings.grade-scales-page'))
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Create Grade Scale</h1>
    
    <form @submit.prevent="save" class="max-w-2xl">
      <div class="bg-white rounded-lg shadow p-6">
        <!-- Name -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border-gray-300 rounded-md shadow-sm"
            placeholder="e.g., Secondary School Grading"
          />
        </div>
        
        <!-- Rounding Mode -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Rounding Mode</label>
          <select v-model="form.rounding_mode" class="w-full border-gray-300 rounded-md shadow-sm">
            <option value="round">Round</option>
            <option value="floor">Floor</option>
            <option value="ceil">Ceil</option>
          </select>
        </div>
        
        <!-- Precision -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Precision (decimal places)</label>
          <input
            v-model.number="form.precision"
            type="number"
            min="0"
            max="2"
            class="w-full border-gray-300 rounded-md shadow-sm"
          />
        </div>
        
        <!-- Is Default -->
        <div class="mb-4">
          <label class="flex items-center">
            <input
              v-model="form.is_default"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 shadow-sm"
            />
            <span class="ml-2 text-sm text-gray-700">Set as default scale</span>
          </label>
        </div>
        
        <!-- Actions -->
        <div class="flex justify-end gap-4">
          <button
            type="button"
            @click="cancel"
            class="px-4 py-2 border rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving || !form.name"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
