<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { alert } from '@/utils'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import Icon from '@/components/Icon.vue'

interface GradeSystemItem {
  id: number
  grade_letter: string
  min_percentage: number
  max_percentage: number
  grade_point: number
  sort_order: number
  is_new?: boolean
}

interface GradeSystem {
  id: number
  name: string
  is_active: boolean
  is_default: boolean
  rounding_mode: string
  precision: number
  grade_system_items: GradeSystemItem[]
}

interface Props {
  gradeSystems: GradeSystem[]
}

const props = defineProps<Props>()

// Create a reactive copy for local use
const localGradeSystems = ref<GradeSystem[]>([])

// Watch for prop changes and update local ref
watch(() => props.gradeSystems, (newVal) => {
  localGradeSystems.value = newVal
}, { immediate: true, deep: true })

const loading = ref(false)
const expandedSystem = ref<number | null>(null)

// Create modal
const showCreateModal = ref(false)
const createForm = ref({
  name: '',
  rounding_mode: 'round',
  precision: 2,
  is_default: false
})
const createErrors = ref<Record<string, string>>({})

// Delete modal
const showDeleteModal = ref(false)
const systemToDelete = ref<GradeSystem | null>(null)

// Editing state
const editingSystem = ref<GradeSystem | null>(null)
const localItems = ref<GradeSystemItem[]>([])

// Open create modal
const openCreateModal = () => {
  createForm.value = {
    name: '',
    rounding_mode: 'round',
    precision: 2,
    is_default: false
  }
  createErrors.value = {}
  showCreateModal.value = true
}

// Reset create form
const resetCreateForm = () => {
  createForm.value = {
    name: '',
    rounding_mode: 'round',
    precision: 2,
    is_default: false
  }
  createErrors.value = {}
}

// Save new grade system
const saveNewSystem = () => {
  if (!createForm.value.name.trim()) return
  
  loading.value = true
  router.post(route('exam.grade-scales.store'), createForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false
      showCreateModal.value = false
      alert.success('Grade system created successfully!')
      router.reload({ only: ['gradeSystems'] })
    },
    onError: (err) => {
      loading.value = false
      createErrors.value = err as Record<string, string>
      if (Object.keys(createErrors.value).length > 0) {
        const firstError = Object.values(createErrors.value)[0]
        alert.error(firstError)
      }
    }
  })
}

// Toggle expand/collapse
const toggleExpand = (system: GradeSystem) => {
  if (expandedSystem.value === system.id) {
    expandedSystem.value = null
  } else {
    expandedSystem.value = system.id
    editingSystem.value = JSON.parse(JSON.stringify(system))
    localItems.value = JSON.parse(JSON.stringify(system.grade_system_items || []))
  }
}

// Set as default
const setAsDefault = (id: number) => {
  // Check if there's already a default system
  const currentDefault = localGradeSystems.value.find(s => s.is_default)
  const targetSystem = localGradeSystems.value.find(s => s.id === id)
  
  if (currentDefault && currentDefault.id !== id) {
    alert.confirm(
      `\"${targetSystem?.name}\" will become the new default grade system. \"${currentDefault.name}\" will no longer be the default. Continue?`,
      'Switch Default Grade System'
    ).then((result) => {
      if (result.isConfirmed) {
        loading.value = true
        router.patch(route('exam.grade-scales.set-default', { id }), {}, {
          preserveScroll: true,
          onSuccess: () => {
            loading.value = false
            alert.success('Default grade system updated successfully!')
            router.reload({ only: ['gradeSystems'] })
          },
          onError: () => {
            loading.value = false
            alert.error('Failed to update default grade system')
          }
        })
      }
    })
  } else {
    loading.value = true
    router.patch(route('exam.grade-scales.set-default', { id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false
        alert.success('Default grade system updated successfully!')
        router.reload({ only: ['gradeSystems'] })
      },
      onError: () => {
        loading.value = false
        alert.error('Failed to update default grade system')
      }
    })
  }
}

// Set as active
const setAsActive = (id: number) => {
  alert.confirm('Set this grade system as active?', 'Set Active')
    .then((result) => {
      if (result.isConfirmed) {
        loading.value = true
        router.patch(route('exam.grade-scales.set-active', { id }), {}, {
          preserveScroll: true,
          onSuccess: () => {
            loading.value = false
            alert.success('Grade system activated successfully!')
            router.reload({ only: ['gradeSystems'] })
          },
          onError: () => {
            loading.value = false
            alert.error('Failed to activate grade system')
          }
        })
      }
    })
}

// Confirm delete
const confirmDelete = (system: GradeSystem) => {
  systemToDelete.value = system
  showDeleteModal.value = true
}

// Delete system
const deleteSystem = () => {
  if (!systemToDelete.value) return
  
  loading.value = true
  router.delete(route('exam.grade-scales.destroy', { id: systemToDelete.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false
      showDeleteModal.value = false
      systemToDelete.value = null
      alert.success('Grade system deleted successfully!')
      router.reload({ only: ['gradeSystems'] })
    },
    onError: () => {
      loading.value = false
      alert.error('Failed to delete grade system')
    }
  })
}

// Check for duplicate grade labels
const checkDuplicateGradeLabels = (): boolean => {
  const labels = localItems.value.map(item => item.grade_letter.trim().toUpperCase()).filter(l => l)
  const duplicates = labels.filter((label, index) => labels.indexOf(label) !== index)
  
  if (duplicates.length > 0) {
    alert.error(`Duplicate grade label found: ${duplicates[0]}. Each grade label must be unique within the system.`)
    return true
  }
  return false
}

// Add new grade item
const addGradeItem = () => {
  if (checkDuplicateGradeLabels()) return
  
  if (!localItems.value) localItems.value = []
  
  const lastItem = localItems.value[localItems.value.length - 1]
  const newMin = lastItem ? Math.max(0, (lastItem.min_percentage || 0) - 10) : 0
  
  localItems.value.push({
    id: Date.now(),
    grade_letter: '',
    min_percentage: newMin,
    max_percentage: newMin + 9,
    grade_point: 0,
    sort_order: localItems.value.length,
    is_new: true
  })
}

// Remove grade item
const removeGradeItem = (index: number) => {
  localItems.value.splice(index, 1)
}

// Save grade system with items
const saveGradeSystem = () => {
  if (!editingSystem.value) return
  
  // Check for duplicate grade labels
  if (checkDuplicateGradeLabels()) return
  
  loading.value = true
  
  // Update the system first
  router.put(route('exam.grade-scales.update', { id: editingSystem.value.id }), {
    name: editingSystem.value.name,
    rounding_mode: editingSystem.value.rounding_mode,
    precision: editingSystem.value.precision,
    is_default: editingSystem.value.is_default
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Then handle items
      saveGradeItems()
    },
    onError: () => {
      loading.value = false
      alert.error('Failed to save grade system')
    }
  })
}

// Save grade items via API
const saveGradeItems = async () => {
  const axios = (await import('axios')).default
  
  // Filter out items that are new (need to be created)
  const newItems = localItems.value.filter(item => item.is_new && item.grade_letter)
  
  // Create new items
  for (const item of newItems) {
    try {
      await axios.post(route('exam.grade-scales.items.store', { id: editingSystem.value?.id }), {
        grade_letter: item.grade_letter,
        min_percentage: item.min_percentage,
        max_percentage: item.max_percentage,
        grade_point: item.grade_point,
        sort_order: item.sort_order
      })
    } catch (error) {
      console.error('Error creating grade item:', error)
    }
  }
  
  loading.value = false
  expandedSystem.value = null
  alert.success('Grade system saved successfully!')
  router.reload({ only: ['gradeSystems'] })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Grade Systems</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Manage grading scales with percentage ranges and grade labels</p>
      </div>
      <Dialog v-model:open="showCreateModal">
        <DialogTrigger as-child>
          <Button @click="openCreateModal">
            <Icon icon="plus" class="mr-1" />
            Create Grade System
          </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[500px]">
          <form @submit.prevent="saveNewSystem" class="space-y-4">
            <DialogHeader>
              <DialogTitle>Create Grade System</DialogTitle>
              <DialogDescription>
                Create a new grade system with custom percentage ranges and grade labels.
              </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
              <div class="grid gap-2">
                <Label for="name">System Name <span class="text-red-500">*</span></Label>
                <Input
                  id="name"
                  v-model="createForm.name"
                  placeholder="e.g., Secondary School Grading"
                />
              </div>

              <div class="grid gap-2">
                <Label for="rounding_mode">Rounding Mode</Label>
                <select
                  id="rounding_mode"
                  v-model="createForm.rounding_mode"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option value="half_up">Half Up - rounds 0.5 up (e.g., 2.5 → 3)</option>
                  <option value="half_down">Half Down - rounds 0.5 down (e.g., 2.5 → 2)</option>
                  <option value="round">Round - Standard rounding (e.g., 2.5 → 3, 2.4 → 2)</option>
                  <option value="floor">Floor - Always round down (e.g., 2.9 → 2)</option>
                  <option value="ceil">Ceiling - Always round up (e.g., 2.1 → 3)</option>
                </select>
              </div>

              <div class="grid gap-2">
                <Label for="precision">Precision (Decimal Places)</Label>
                <Input
                  id="precision"
                  v-model.number="createForm.precision"
                  type="number"
                  min="0"
                  max="2"
                />
              </div>

              <div class="flex items-center space-x-2">
                <input
                  id="is_default"
                  v-model="createForm.is_default"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <Label for="is_default">Set as default grade system</Label>
              </div>
            </div>

            <DialogFooter>
              <DialogClose as-child>
                <Button type="button" variant="secondary" @click="resetCreateForm">
                  Cancel
                </Button>
              </DialogClose>
              <Button type="submit" :disabled="loading || !createForm.name">
                {{ loading ? 'Creating...' : 'Create' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>

    <!-- Empty State -->
    <div v-if="localGradeSystems.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <Icon icon="academic-cap" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
      <p class="text-gray-500 dark:text-gray-400 mb-4">No grade systems found. Create one to get started.</p>
      <Button @click="openCreateModal">
        <Icon icon="plus" class="mr-1" />
        Create Grade System
      </Button>
    </div>

    <!-- Grade Systems List -->
    <div v-else class="space-y-4">
      <div
        v-for="system in localGradeSystems"
        :key="system.id"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
      >
        <!-- System Header -->
        <div 
          class="p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          @click="toggleExpand(system)"
        >
          <div class="flex items-center gap-3 min-w-0">
            <svg 
              class="w-5 h-5 text-gray-400 transition-transform flex-shrink-0"
              :class="{ 'rotate-90': expandedSystem === system.id }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <div class="min-w-0">
              <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ system.name }}</h3>
              <div class="flex flex-wrap gap-2 mt-1">
                <span
                  v-if="system.is_default"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                >
                  <Icon icon="star" class="w-3 h-3 mr-1" />
                  Default
                </span>
                <span
                  v-if="system.is_active"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                >
                  <Icon icon="check" class="w-3 h-3 mr-1" />
                  Active
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ system.grade_system_items?.length || 0 }} grade{{ (system.grade_system_items?.length || 0) !== 1 ? 's' : '' }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-2 flex-shrink-0" @click.stop>
            <Button
              v-if="!system.is_default"
              variant="outline"
              size="sm"
              @click="setAsDefault(system.id)"
              :disabled="loading"
            >
              <Icon icon="star" class="w-4 h-4 mr-1" />
              Set Default
            </Button>
            <Button
              v-if="!system.is_active"
              variant="outline"
              size="sm"
              @click="setAsActive(system.id)"
              :disabled="loading"
            >
              <Icon icon="check" class="w-4 h-4 mr-1" />
              Set Active
            </Button>
            <Button
              v-if="!system.is_active && !system.is_default"
              variant="ghost"
              size="sm"
              @click="confirmDelete(system)"
              class="text-red-600 hover:text-red-800 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
            >
              <Icon icon="trash" class="w-4 h-4" />
            </Button>
          </div>
        </div>

        <!-- Expanded Content -->
        <div v-if="expandedSystem === system.id" class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div v-if="editingSystem" class="p-4 space-y-4">
            <!-- Edit System Form -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="grid gap-2">
                <Label>System Name</Label>
                <Input
                  v-model="editingSystem.name"
                  placeholder="e.g., Secondary School Grading"
                />
              </div>
              <div class="grid gap-2">
                <Label>Rounding Mode</Label>
                <select
                  v-model="editingSystem.rounding_mode"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option value="half_up">Half Up - rounds 0.5 up (e.g., 2.5 → 3)</option>
                  <option value="half_down">Half Down - rounds 0.5 down (e.g., 2.5 → 2)</option>
                  <option value="round">Round - Standard rounding (e.g., 2.5 → 3, 2.4 → 2)</option>
                  <option value="floor">Floor - Always round down (e.g., 2.9 → 2)</option>
                  <option value="ceil">Ceiling - Always round up (e.g., 2.1 → 3)</option>
                </select>
              </div>
              <div class="grid gap-2">
                <Label>Precision</Label>
                <Input
                  v-model.number="editingSystem.precision"
                  type="number"
                  min="0"
                  max="2"
                />
              </div>
            </div>

            <!-- Grade Items Section -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 overflow-hidden">
              <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <div>
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Grade Items</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Define percentage ranges and corresponding grade labels</p>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  @click="addGradeItem"
                >
                  <Icon icon="plus" class="w-4 h-4 mr-1" />
                  Add Grade
                </Button>
              </div>
              
              <!-- Grade Items Table -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Grade Label
                      </th>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Min %
                      </th>
                      <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Max %
                      </th>
                      <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    <tr 
                      v-for="(item, index) in localItems" 
                      :key="item.id"
                      class="hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                      <td class="px-4 py-2">
                        <Input
                          v-model="item.grade_letter"
                          placeholder="e.g., A+"
                          class="w-24"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <Input
                          v-model.number="item.min_percentage"
                          type="number"
                          min="0"
                          max="100"
                          placeholder="0"
                          class="w-20"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <Input
                          v-model.number="item.max_percentage"
                          type="number"
                          min="0"
                          max="100"
                          placeholder="100"
                          class="w-20"
                        />
                      </td>
                      <td class="px-4 py-2 text-right">
                        <Button
                          variant="ghost"
                          size="sm"
                          @click="removeGradeItem(index)"
                          class="text-red-600 hover:text-red-800 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                        >
                          <Icon icon="trash" class="w-4 h-4" />
                        </Button>
                      </td>
                    </tr>
                    <tr v-if="localItems.length === 0">
                      <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                          <Icon icon="document-text" class="w-8 h-8 text-gray-400 mb-2" />
                          <p class="text-sm">No grades defined.</p>
                          <p class="text-xs">Click "Add Grade" to create grade items.</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end gap-2 pt-2">
              <Button
                variant="outline"
                @click="expandedSystem = null"
              >
                Cancel
              </Button>
              <Button
                @click="saveGradeSystem"
                :disabled="loading || !editingSystem.name"
              >
                <Icon v-if="loading" icon="refresh" class="w-4 h-4 mr-1 animate-spin" />
                <Icon v-else icon="save" class="w-4 h-4 mr-1" />
                {{ loading ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:open="showDeleteModal">
      <DialogContent class="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle>Delete Grade System</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete "{{ systemToDelete?.name }}"? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose as-child>
            <Button variant="secondary">
              Cancel
            </Button>
          </DialogClose>
          <Button variant="destructive" @click="deleteSystem" :disabled="loading">
            <Icon icon="trash" class="w-4 h-4 mr-1" />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
