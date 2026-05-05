<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface GradeScale {
  id: number
  name: string
  is_active: boolean
  is_default: boolean
  rounding_mode: string
  precision: number
  grade_system_items: GradeScaleItem[]
}

interface GradeScaleItem {
  id: number
  grade_label: string
  min_percentage: number
  max_percentage: number | null
  grade_point: number | null
  order: number
}

defineProps<{
  gradeScales: GradeScale[]
}>()

const loading = ref(false)
const showDeleteModal = ref(false)
const scaleToDelete = ref<GradeScale | null>(null)

const createNew = () => {
  router.get(route('exam.settings.grade-scales.create-page'))
}

const editScale = (id: number) => {
  router.get(route('exam.settings.grade-scales.edit-page', { id }))
}

const setActive = (id: number) => {
  loading.value = true
  router.patch(route('exam.grade-scales.set-active', { id }), {}, {
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const confirmDelete = (scale: GradeScale) => {
  scaleToDelete.value = scale
  showDeleteModal.value = true
}

const deleteScale = () => {
  if (scaleToDelete.value) {
    loading.value = true
    router.delete(route('exam.grade-scales.destroy', { id: scaleToDelete.value.id }), {
      preserveScroll: true,
      onFinish: () => {
        loading.value = false
        showDeleteModal.value = false
        scaleToDelete.value = null
      }
    })
  }
}
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Grade Scales</h1>
      <button
        @click="createNew"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
      >
        Create New Scale
      </button>
    </div>
    
    <div v-if="gradeScales.length === 0" class="text-center py-12 text-gray-500">
      No grade scales found. Create one to get started.
    </div>
    
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="scale in gradeScales" 
        :key="scale.id"
        class="bg-white rounded-lg shadow p-6"
        :class="{ 'ring-2 ring-blue-500': scale.is_active }"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-lg font-semibold">{{ scale.name }}</h3>
            <div class="flex gap-2 mt-1">
              <span 
                v-if="scale.is_active" 
                class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded"
              >
                Active
              </span>
              <span 
                v-if="scale.is_default" 
                class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
              >
                Default
              </span>
            </div>
          </div>
          <div class="flex gap-2">
            <button
              v-if="!scale.is_active"
              @click="setActive(scale.id)"
              class="text-blue-600 hover:text-blue-800 text-sm"
              :disabled="loading"
            >
              Set Active
            </button>
            <button
              @click="editScale(scale.id)"
              class="text-gray-600 hover:text-gray-800"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              v-if="!scale.is_active && !scale.is_default"
              @click="confirmDelete(scale)"
              class="text-red-600 hover:text-red-800"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
        
        <div class="text-sm text-gray-500">
          {{ scale.grade_system_items?.length || 0 }} grade items
        </div>
      </div>
    </div>
    
    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md">
        <h3 class="text-lg font-semibold mb-4">Delete Grade Scale</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete "{{ scaleToDelete?.name }}"? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-4">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 border rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="deleteScale"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
