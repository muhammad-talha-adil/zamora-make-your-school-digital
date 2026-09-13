<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface GradeScale {
  id: number
  name: string
  rounding_mode: string
  precision: number
  is_default: boolean
}

const props = defineProps<{
  gradeScale: GradeScale
}>()

const form = ref({
  name: props.gradeScale.name,
  rounding_mode: props.gradeScale.rounding_mode,
  precision: props.gradeScale.precision,
  is_default: props.gradeScale.is_default,
})

const saving = ref(false)

const save = () => {
  saving.value = true
  router.put(route('exam.grade-scales.update', { id: props.gradeScale.id }), form.value, {
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
    <h1 class="text-2xl font-bold mb-6">Edit Grade Scale</h1>

    <form @submit.prevent="save" class="max-w-2xl">
      <div class="bg-card rounded-lg shadow p-6">
        <!-- Name -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-muted-foreground mb-1">Name *</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border-border rounded-md shadow-sm"
            placeholder="e.g., Secondary School Grading"
          />
        </div>

        <!-- Rounding Mode -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-muted-foreground mb-1">Rounding Mode</label>
          <select v-model="form.rounding_mode" class="w-full border-border rounded-md shadow-sm">
            <option value="round">Round</option>
            <option value="floor">Floor</option>
            <option value="ceil">Ceil</option>
          </select>
        </div>

        <!-- Precision -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-muted-foreground mb-1">Precision (decimal places)</label>
          <input
            v-model.number="form.precision"
            type="number"
            min="0"
            max="2"
            class="w-full border-border rounded-md shadow-sm"
          />
        </div>

        <!-- Is Default -->
        <div class="mb-4">
          <label class="flex items-center">
            <input
              v-model="form.is_default"
              type="checkbox"
              class="rounded border-border text-primary shadow-sm"
            />
            <span class="ml-2 text-sm text-muted-foreground">Set as default scale</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap justify-end gap-4">
          <button
            type="button"
            @click="cancel"
            class="px-4 py-2 border rounded-lg hover:bg-accent"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="saving || !form.name"
            class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
